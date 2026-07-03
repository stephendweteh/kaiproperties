<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Property;
use App\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $baseQuery = Ticket::query();

        $metrics = [
            'total' => (clone $baseQuery)->count(),
            'new' => (clone $baseQuery)->where('status', 'logged')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'overdue' => (clone $baseQuery)
                ->whereNotIn('status', ['completed', 'closed', 'rejected'])
                ->whereNotNull('etd')
                ->where('etd', '<', now())
                ->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'closed' => (clone $baseQuery)->where('status', 'closed')->count(),
        ];

        try {
            $hasCustomersTable = Schema::hasTable('customers');
            $hasPropertiesCustomerId = Schema::hasColumn('properties', 'customer_id');
        } catch (QueryException) {
            $hasCustomersTable = false;
            $hasPropertiesCustomerId = false;
        }

        try {
            $customerMetrics = $hasCustomersTable
                ? [
                    'total' => Customer::query()->count(),
                    'active' => Customer::query()->where('is_active', true)->count(),
                    'with_properties' => $hasPropertiesCustomerId ? Customer::query()->has('properties')->count() : 0,
                    'without_properties' => $hasPropertiesCustomerId ? Customer::query()->doesntHave('properties')->count() : Customer::query()->count(),
                ]
                : [
                    'total' => 0,
                    'active' => 0,
                    'with_properties' => 0,
                    'without_properties' => 0,
                ];
        } catch (QueryException) {
            $customerMetrics = [
                'total' => 0,
                'active' => 0,
                'with_properties' => 0,
                'without_properties' => 0,
            ];
            $hasCustomersTable = false;
        }

        try {
            $propertyMetrics = [
                'total' => Property::query()->count(),
                'active' => Property::query()->where('is_active', true)->count(),
                'assigned' => $hasPropertiesCustomerId ? Property::query()->whereNotNull('customer_id')->count() : 0,
                'unassigned' => $hasPropertiesCustomerId ? Property::query()->whereNull('customer_id')->count() : Property::query()->count(),
            ];
        } catch (QueryException) {
            $propertyMetrics = [
                'total' => Property::query()->count(),
                'active' => Property::query()->where('is_active', true)->count(),
                'assigned' => 0,
                'unassigned' => Property::query()->count(),
            ];
            $hasPropertiesCustomerId = false;
        }

        $ticketStatusBreakdown = collect([
            ['label' => 'Logged/New', 'value' => $metrics['new'], 'class' => 'status-logged'],
            ['label' => 'In Progress', 'value' => $metrics['in_progress'], 'class' => 'status-in_progress'],
            ['label' => 'Overdue', 'value' => $metrics['overdue'], 'class' => 'status-overdue'],
            ['label' => 'Completed', 'value' => $metrics['completed'], 'class' => 'status-completed'],
            ['label' => 'Closed', 'value' => $metrics['closed'], 'class' => 'status-closed'],
        ])->map(function (array $item) use ($metrics): array {
            $total = max($metrics['total'], 1);
            $item['percentage'] = (int) round(($item['value'] / $total) * 100);

            return $item;
        });

        $workload = Ticket::query()
            ->select('users.name', DB::raw('COUNT(tickets.id) as tickets_count'))
            ->join('users', 'users.id', '=', 'tickets.assigned_to')
            ->groupBy('users.name')
            ->orderByDesc('tickets_count')
            ->get();

        $propertyStats = Property::query()
            ->select('properties.name', DB::raw('COUNT(tickets.id) as tickets_count'))
            ->leftJoin('tickets', 'tickets.property_id', '=', 'properties.id')
            ->groupBy('properties.id', 'properties.name')
            ->orderByDesc('tickets_count')
            ->orderBy('properties.name')
            ->get();

        try {
            $topCustomers = ($hasCustomersTable && $hasPropertiesCustomerId)
                ? Customer::query()
                    ->select('customers.name', DB::raw('COUNT(properties.id) as properties_count'))
                    ->leftJoin('properties', 'properties.customer_id', '=', 'customers.id')
                    ->groupBy('customers.id', 'customers.name')
                    ->orderByDesc('properties_count')
                    ->orderBy('customers.name')
                    ->limit(8)
                    ->get()
                : collect();
        } catch (QueryException) {
            $topCustomers = collect();
        }

        return view('dashboard', [
            'metrics' => $metrics,
            'customerMetrics' => $customerMetrics,
            'propertyMetrics' => $propertyMetrics,
            'ticketStatusBreakdown' => $ticketStatusBreakdown,
            'workload' => $workload,
            'propertyStats' => $propertyStats,
            'topCustomers' => $topCustomers,
        ]);
    }
}
