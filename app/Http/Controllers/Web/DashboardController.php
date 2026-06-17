<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
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

        return view('dashboard', [
            'metrics' => $metrics,
            'workload' => $workload,
            'propertyStats' => $propertyStats,
        ]);
    }
}
