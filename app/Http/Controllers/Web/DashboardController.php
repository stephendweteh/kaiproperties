<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Property;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $customerMetrics = [
            'total' => Customer::query()->count(),
            'active' => Customer::query()->where('is_active', true)->count(),
            'with_properties' => Customer::query()->has('properties')->count(),
            'without_properties' => Customer::query()->doesntHave('properties')->count(),
        ];

        $propertyMetrics = [
            'total' => Property::query()->count(),
            'active' => Property::query()->where('is_active', true)->count(),
            'assigned' => Property::query()->whereNotNull('customer_id')->count(),
            'unassigned' => Property::query()->whereNull('customer_id')->count(),
        ];

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

        $topCustomers = Customer::query()
            ->select('customers.name', DB::raw('COUNT(properties.id) as properties_count'))
            ->leftJoin('properties', 'properties.customer_id', '=', 'customers.id')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('properties_count')
            ->orderBy('customers.name')
            ->limit(8)
            ->get();

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
