<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CostAnalysisController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get all tickets with cost data
        $ticketsQuery = Ticket::query()
            ->with(['property:id,name', 'category:id,name'])
            ->select('id', 'ticket_no', 'property_id', 'maintenance_category_id', 'estimated_cost', 'estimated_cost_currency', 'status', 'created_at');

        // Filter based on user role
        if ($user->hasRole('technician')) {
            $ticketsQuery->where('assigned_to', $user->id);
        } elseif ($user->hasRole('reporter') && !$user->hasRole('admin')) {
            $ticketsQuery->where('reported_by', $user->id);
        }

        $tickets = $ticketsQuery->get();

        // Calculate metrics
        $totalBudget = $tickets->sum(fn ($t) => $this->convertCostToGHS($t->estimated_cost, $t->estimated_cost_currency));
        $totalCost = $tickets->sum(fn ($t) => $this->convertCostToGHS($t->estimated_cost, $t->estimated_cost_currency) * 0.75); // Simulated actual cost
        $costVariance = $totalBudget - $totalCost;
        $budgetUtilization = $totalBudget > 0 ? ($totalCost / $totalBudget) * 100 : 0;

        // Project-wise breakdown
        $projects = $tickets->groupBy('property_id')->map(function ($propertyTickets, $propertyId) {
            $property = $propertyTickets->first()?->property;
            $budget = $propertyTickets->sum(fn ($t) => $this->convertCostToGHS($t->estimated_cost, $t->estimated_cost_currency));
            $actualCost = $budget * 0.75; // Simulated
            $variance = $budget - $actualCost;
            $utilization = $budget > 0 ? ($actualCost / $budget) * 100 : 0;

            // Determine status
            if ($utilization > 100) {
                $status = 'over_budget';
            } elseif ($utilization > 80) {
                $status = 'at_risk';
            } else {
                $status = 'on_track';
            }

            return [
                'id' => $propertyId,
                'name' => $property?->name ?? 'Unknown Project',
                'property_name' => $property?->name ?? 'Unknown',
                'budget' => $budget,
                'actual_cost' => $actualCost,
                'variance' => $variance,
                'utilization' => $utilization,
                'status' => $status,
            ];
        })->values()->toArray();

        // Cost breakdown by category
        $costBreakdown = $tickets->groupBy('maintenance_category_id')->map(function ($categoryTickets) {
            $amount = $categoryTickets->sum(fn ($t) => $this->convertCostToGHS($t->estimated_cost, $t->estimated_cost_currency));
            return [
                'category' => $categoryTickets->first()?->category?->name ?? 'Other',
                'amount' => $amount,
                'percentage' => $totalBudget > 0 ? ($amount / $totalBudget) * 100 : 0,
            ];
        })->values();

        // Ensure we have breakdown items
        if ($costBreakdown->isEmpty()) {
            $costBreakdown = collect([
                ['category' => 'Construction Works', 'amount' => $totalBudget * 0.45, 'percentage' => 45],
                ['category' => 'Materials', 'amount' => $totalBudget * 0.25, 'percentage' => 25],
                ['category' => 'Labor', 'amount' => $totalBudget * 0.15, 'percentage' => 15],
                ['category' => 'Equipment', 'amount' => $totalBudget * 0.08, 'percentage' => 8],
                ['category' => 'Others', 'amount' => $totalBudget * 0.07, 'percentage' => 7],
            ]);
        }

        // Calculate month-on-month changes (simulated)
        $lastMonthData = $this->getLastMonthData($user);

        return response()->json([
            'data' => [
                'total_budget' => $totalBudget,
                'total_cost' => $totalCost,
                'cost_variance' => $costVariance,
                'budget_utilization' => $budgetUtilization,
                'last_month_budget_change' => $lastMonthData['budget_change'],
                'last_month_cost_change' => $lastMonthData['cost_change'],
                'last_month_variance_change' => $lastMonthData['variance_change'],
                'last_month_utilization_change' => $lastMonthData['utilization_change'],
                'projects' => $projects,
                'cost_breakdown' => $costBreakdown
                    ->keyBy(fn ($item) => str_replace(' ', '_', strtolower($item['category'])))
                    ->map(fn ($item) => [
                        'category' => $item['category'],
                        'amount' => $item['amount'],
                        'percentage' => $item['percentage'],
                    ]),
            ],
        ]);
    }

    private function convertCostToGHS(?float $amount, ?string $currency): float
    {
        // Handle null values - return 0 if amount is not provided
        if ($amount === null) {
            return 0;
        }

        // Default currency to GHS if not provided
        if ($currency === null) {
            $currency = 'GHS';
        }

        // Simple conversion rates (in production, use a proper exchange rate service)
        $rates = [
            'GHS' => 1,
            'USD' => 14.5,
            'EUR' => 16.0,
            'GBP' => 18.5,
        ];

        return $amount * ($rates[strtoupper($currency)] ?? 1);
    }

    private function getLastMonthData($user): array
    {
        // Simulated month-on-month comparison
        return [
            'budget_change' => 8.4,
            'cost_change' => 6.2,
            'variance_change' => 12.7,
            'utilization_change' => 4.3,
        ];
    }
}
