<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user            = $request->user();
        $isTechnicianRole = $user->hasRole(User::ROLE_TECHNICIAN);
        $isTenantRole     = $user->hasRole(User::ROLE_TENANT);

        $baseQuery = Ticket::query();

        if ($isTechnicianRole) {
            $baseQuery->where('assigned_to', $user->id);
        } elseif ($isTenantRole) {
            $baseQuery->where('reported_by', $user->id);
        }

        $metrics = [
            'total'       => (clone $baseQuery)->count(),
            'new'         => (clone $baseQuery)->where('status', 'logged')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'overdue'     => (clone $baseQuery)
                ->whereNotIn('status', ['completed', 'closed', 'rejected'])
                ->whereNotNull('etd')
                ->where('etd', '<', now())
                ->count(),
            'completed'   => (clone $baseQuery)->where('status', 'completed')->count(),
            'closed'      => (clone $baseQuery)->where('status', 'closed')->count(),
        ];

        $recentTickets = Ticket::query()
            ->with(['property:id,name', 'category:id,name'])
            ->when($isTechnicianRole, fn ($q) => $q->where('assigned_to', $user->id))
            ->when($isTenantRole, fn ($q) => $q->where('reported_by', $user->id))
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Ticket $t) => [
                'id'         => $t->id,
                'ticket_no'  => $t->ticket_no,
                'title'      => $t->title,
                'status'     => $t->status,
                'priority'   => $t->priority,
                'property'   => $t->property?->name,
                'category'   => $t->category?->name,
                'created_at' => $t->created_at?->toIso8601String(),
            ]);

        $byStatus = Ticket::query()
            ->when($isTechnicianRole, fn ($q) => $q->where('assigned_to', $user->id))
            ->when($isTenantRole, fn ($q) => $q->where('reported_by', $user->id))
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'metrics'        => $metrics,
            'by_status'      => $byStatus,
            'recent_tickets' => $recentTickets,
        ]);
    }
}
