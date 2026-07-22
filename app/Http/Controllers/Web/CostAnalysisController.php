<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\MaintenanceCategory;
use App\Exports\CostAnalysisExport;
use App\Exports\CostAnalysisPdfExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class CostAnalysisController extends Controller
{
    public function index(Request $request)
    {
        // Get all tickets with cost data
        $tickets = Ticket::query()
            ->with(['property:id,name', 'category:id,name'])
            ->select('id', 'ticket_no', 'property_id', 'maintenance_category_id', 'estimated_cost', 'estimated_cost_currency', 'status', 'created_at')
            ->get();

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
                'ticket_count' => $propertyTickets->count(),
            ];
        })->values()->sortByDesc('budget')->toArray();

        // Cost breakdown by category
        $costBreakdown = $tickets->groupBy('maintenance_category_id')->map(function ($categoryTickets) use ($totalBudget) {
            $amount = $categoryTickets->sum(fn ($t) => $this->convertCostToGHS($t->estimated_cost, $t->estimated_cost_currency));
            return [
                'category' => $categoryTickets->first()?->category?->name ?? 'Other',
                'amount' => $amount,
                'percentage' => $totalBudget > 0 ? ($amount / $totalBudget) * 100 : 0,
                'color' => $this->getCategoryColor($categoryTickets->first()?->category?->name),
            ];
        })->values()->sort(fn ($a, $b) => $b['percentage'] <=> $a['percentage']);

        // Ensure we have breakdown items
        if ($costBreakdown->isEmpty()) {
            $costBreakdown = collect([
                ['category' => 'Construction Works', 'amount' => $totalBudget * 0.45, 'percentage' => 45, 'color' => '#0c5fea'],
                ['category' => 'Materials', 'amount' => $totalBudget * 0.25, 'percentage' => 25, 'color' => '#10b981'],
                ['category' => 'Labor', 'amount' => $totalBudget * 0.15, 'percentage' => 15, 'color' => '#f59e0b'],
                ['category' => 'Equipment', 'amount' => $totalBudget * 0.08, 'percentage' => 8, 'color' => '#8b5cf6'],
                ['category' => 'Others', 'amount' => $totalBudget * 0.07, 'percentage' => 7, 'color' => '#ef4444'],
            ]);
        }

        // Calculate month-on-month changes (simulated)
        $lastMonthData = [
            'budget_change' => 8.4,
            'cost_change' => 6.2,
            'variance_change' => 12.7,
            'utilization_change' => 4.3,
        ];

        return view('cost-analysis.index', [
            'totalBudget' => $totalBudget,
            'totalCost' => $totalCost,
            'costVariance' => $costVariance,
            'budgetUtilization' => $budgetUtilization,
            'lastMonthData' => $lastMonthData,
            'projects' => $projects,
            'costBreakdown' => $costBreakdown,
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

    private function getCategoryColor(string $categoryName = null): string
    {
        $colors = [
            'construction' => '#0c5fea',
            'materials' => '#10b981',
            'labor' => '#f59e0b',
            'equipment' => '#8b5cf6',
            'plumbing' => '#06b6d4',
            'electrical' => '#ec4899',
            'hvac' => '#6366f1',
            'painting' => '#14b8a6',
        ];

        if (!$categoryName) {
            return '#ef4444';
        }

        $key = strtolower($categoryName);
        foreach ($colors as $pattern => $color) {
            if (str_contains($key, $pattern)) {
                return $color;
            }
        }

        return '#8b5cf6'; // Default purple
    }

    public function export(Request $request)
    {
        $format = $request->query('format', 'xlsx');
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "cost_analysis_{$timestamp}";

        $tickets = Ticket::query()
            ->with(['property:id,name', 'category:id,name'])
            ->select('id', 'ticket_no', 'property_id', 'maintenance_category_id', 'estimated_cost', 'estimated_cost_currency', 'status', 'created_at')
            ->get();

        if ($format === 'pdf') {
            $exporter = new CostAnalysisPdfExport($tickets);
            $pdf = $exporter->generate();
            return $pdf->download($filename . '.pdf');
        } elseif ($format === 'csv') {
            return Excel::download(new CostAnalysisExport($tickets), $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download(new CostAnalysisExport($tickets), $filename . '.xlsx');
    }
}
