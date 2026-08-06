<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
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

        if (Schema::hasTable('ticket_technician')) {
            $byTechnician = Ticket::query()
                ->select('users.id', 'users.name', DB::raw('COUNT(tickets.id) as tickets_count'))
                ->join('ticket_technician', 'ticket_technician.ticket_id', '=', 'tickets.id')
                ->join('users', 'users.id', '=', 'ticket_technician.user_id')
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('tickets_count')
                ->get();
        } elseif (Schema::hasColumn('tickets', 'assigned_to')) {
            $byTechnician = Ticket::query()
                ->select('users.id', 'users.name', DB::raw('COUNT(tickets.id) as tickets_count'))
                ->join('users', 'users.id', '=', 'tickets.assigned_to')
                ->whereNotNull('tickets.assigned_to')
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('tickets_count')
                ->get();
        } else {
            $byTechnician = collect();
        }

        $byProperty = Ticket::query()
            ->select('properties.id', 'properties.name', DB::raw('COUNT(tickets.id) as tickets_count'))
            ->join('properties', 'properties.id', '=', 'tickets.property_id')
            ->groupBy('properties.id', 'properties.name')
            ->orderByDesc('tickets_count')
            ->get();

        return response()->json([
            'metrics' => $metrics,
            'metric_labels' => [
                'total' => 'Total Tasks',
                'new' => 'New',
                'in_progress' => 'In Progress',
                'overdue' => 'Overdue',
                'completed' => 'Completed',
                'closed' => 'Closed',
            ],
            'status_labels' => [
                'logged' => 'Logged/New',
                'assigned' => 'Assigned',
                'in_progress' => 'In Progress',
                'pending_approval' => 'Pending Approval',
                'on_hold' => 'On Hold',
                'completed' => 'Completed',
                'closed' => 'Closed',
                'rejected' => 'Rejected',
                'overdue' => 'Overdue',
            ],
            'by_technician' => $byTechnician,
            'by_property' => $byProperty,
        ]);
    }
}
