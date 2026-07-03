<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CostRequestReviewRequest;
use App\Http\Requests\Api\CostRequestStoreRequest;
use App\Http\Requests\Api\TicketAssignRequest;
use App\Http\Requests\Api\TicketStatusRequest;
use App\Http\Requests\Api\TicketStoreRequest;
use App\Http\Resources\CostRequestResource;
use App\Http\Resources\TicketResource;
use App\Models\CostRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $isReporterScopedRole = $this->isReporterScopedRole($user);

        $query = Ticket::query()
            ->with(['property', 'category', 'reporter:id,name,email', 'technician:id,name,email'])
            ->when($request->filled('status'), fn (Builder $builder) => $builder->where('status', $request->string('status')))
            ->when($request->filled('property_id'), fn (Builder $builder) => $builder->where('property_id', $request->integer('property_id')))
            ->when($request->filled('maintenance_category_id'), fn (Builder $builder) => $builder->where('maintenance_category_id', $request->integer('maintenance_category_id')))
            ->when($request->filled('assigned_to'), fn (Builder $builder) => $builder->where('assigned_to', $request->integer('assigned_to')))
            ->when($request->filled('search'), function (Builder $builder) use ($request): void {
                $search = $request->string('search');

                $builder->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->where('ticket_no', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest();

        if ($isReporterScopedRole) {
            $query->where('reported_by', $user->id);
        }

        if ($user->hasRole(User::ROLE_TECHNICIAN)) {
            $query->where('assigned_to', $user->id)
                ->whereIn('status', $this->technicianVisibleStatuses());
        }

        $tickets = $query->paginate($request->integer('per_page', 15));

        return TicketResource::collection($tickets);
    }

    public function store(TicketStoreRequest $request)
    {
        if (! $this->canCreateTickets($request->user())) {
            return response()->json(['message' => 'You do not have permission to log tickets.'], 403);
        }

        $validated = $request->validated();
        $status = $this->mustGoThroughOperationsApproval($request->user()) ? 'pending_approval' : 'logged';

        $ticket = Ticket::create([
            ...$validated,
            'reported_by' => $request->user()->id,
            'status' => $status,
            'priority' => $validated['priority'] ?? 'medium',
        ]);

        $ticketForNotification = $ticket->fresh(['reporter', 'technician']);
        app()->terminating(function () use ($ticketForNotification): void {
            $this->notificationService->sendTicketLogged($ticketForNotification);
        });

        return response()->json([
            'message' => $status === 'pending_approval'
                ? 'Ticket created and sent for approval.'
                : 'Ticket created successfully.',
            'data' => TicketResource::make($ticket->load(['property', 'category', 'reporter'])),
        ], 201);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->authorizeTicketAccess($request->user(), $ticket);

        return response()->json([
            'data' => TicketResource::make($ticket->load([
                'property',
                'category',
                'reporter:id,name,email',
                'technician:id,name,email',
                'costRequests.requester:id,name,email',
                'costRequests.reviewer:id,name,email',
            ])),
        ]);
    }

    public function assign(TicketAssignRequest $request, Ticket $ticket)
    {
        $user = $request->user();

        if (! $user->hasRole([User::ROLE_ADMIN, User::ROLE_OPERATIONS_MANAGER])) {
            return response()->json(['message' => 'You do not have permission to approve or assign tickets.'], 403);
        }

        $validated = $request->validated();

        $technician = User::where('id', $validated['assigned_to'])
            ->where('role', User::ROLE_TECHNICIAN)
            ->first();

        if (! $technician) {
            return response()->json(['message' => 'Selected user is not a technician.'], 422);
        }

        $attributes = ['assigned_to' => $validated['assigned_to']];

        if (in_array($ticket->status, ['logged', 'pending_approval', 'rejected'], true)) {
            $attributes['status'] = 'assigned';
        }

        $ticket->update($attributes);

        $ticketForNotification = $ticket->fresh();
        app()->terminating(function () use ($ticketForNotification): void {
            $this->notificationService->sendTicketAssigned($ticketForNotification);
        });

        return response()->json([
            'message' => 'Ticket assigned successfully.',
            'data' => TicketResource::make($ticket->fresh()->load(['property', 'category', 'reporter', 'technician'])),
        ]);
    }

    public function changeStatus(TicketStatusRequest $request, Ticket $ticket)
    {
        $user = $request->user();
        $previousStatus = $ticket->status;

        if ($user->hasRole(User::ROLE_TECHNICIAN) && (int) $ticket->assigned_to !== (int) $user->id) {
            return response()->json(['message' => 'You can only update your assigned tickets.'], 403);
        }

        if ($user->hasRole(User::ROLE_TECHNICIAN) && ! in_array($request->string('status')->toString(), ['assigned', 'in_progress', 'completed'], true)) {
            return response()->json(['message' => 'Technicians can only move assigned work to in progress or completed.'], 403);
        }

        if ($user->hasRole(User::ROLE_TENANT)) {
            return response()->json(['message' => 'Tenants cannot change ticket status.'], 403);
        }

        if (
            $user->hasRole(User::ROLE_OPERATIONS_MANAGER)
            && in_array($request->string('status')->toString(), ['logged', 'on_hold'], true)
            && empty($ticket->assigned_to)
        ) {
            return response()->json(['message' => 'Assign a technician before approving or placing this ticket on hold.'], 422);
        }

        $validated = $request->validated();
        $previousStatus = $ticket->status;

        $attributes = ['status' => $validated['status']];

        if ($validated['status'] === 'in_progress' && ! $ticket->started_at) {
            $attributes['started_at'] = now();
        }

        if ($validated['status'] === 'completed') {
            $attributes['completed_at'] = now();
        }

        if ($validated['status'] === 'closed') {
            $attributes['closed_at'] = now();
        }

        $ticket->update($attributes);

        if ($previousStatus !== $ticket->status) {
            $ticketForNotification = $ticket->fresh();
            app()->terminating(function () use ($ticketForNotification, $previousStatus): void {
                $this->notificationService->sendTicketStatusChanged($ticketForNotification, $previousStatus);
            });
        }

        return response()->json([
            'message' => 'Ticket status updated.',
            'data' => TicketResource::make($ticket->fresh()->load(['property', 'category', 'reporter', 'technician'])),
        ]);
    }

    public function submitCostRequest(CostRequestStoreRequest $request, Ticket $ticket)
    {
        $user = $request->user();

        if (! $user->hasRole(User::ROLE_TECHNICIAN) || (int) $ticket->assigned_to !== (int) $user->id) {
            return response()->json(['message' => 'Only the assigned technician can submit a cost request.'], 403);
        }

        $validated = $request->validated();
        $previousStatus = $ticket->status;

        $costRequest = CostRequest::create([
            'ticket_id' => $ticket->id,
            'requested_by' => $user->id,
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        $ticket->update([
            'status' => 'pending_approval',
            'requires_additional_cost' => true,
        ]);

        if ($previousStatus !== $ticket->status) {
            $ticketForNotification = $ticket->fresh();
            app()->terminating(function () use ($ticketForNotification, $previousStatus): void {
                $this->notificationService->sendTicketStatusChanged($ticketForNotification, $previousStatus);
            });
        }

        return response()->json([
            'message' => 'Cost request submitted for approval.',
            'data' => CostRequestResource::make($costRequest->load(['requester', 'reviewer'])),
        ], 201);
    }

    public function reviewCostRequest(CostRequestReviewRequest $request, CostRequest $costRequest)
    {
        $user = $request->user();

        if (! $user->hasRole([User::ROLE_OPERATIONS_MANAGER, User::ROLE_ADMIN])) {
            return response()->json(['message' => 'Only operations managers or admins can review cost requests.'], 403);
        }

        $validated = $request->validated();
        $previousStatus = $costRequest->ticket->status;

        $costRequest->update([
            'status' => $validated['status'],
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'reviewer_comment' => $validated['reviewer_comment'] ?? null,
        ]);

        $nextTicketStatus = $validated['status'] === 'approved' ? 'in_progress' : 'on_hold';

        $costRequest->ticket()->update(['status' => $nextTicketStatus]);

        if ($previousStatus !== $nextTicketStatus) {
            $ticketForNotification = $costRequest->ticket->fresh();
            app()->terminating(function () use ($ticketForNotification, $previousStatus): void {
                $this->notificationService->sendTicketStatusChanged($ticketForNotification, $previousStatus);
            });
        }

        $this->notificationService->sendCostRequestReviewed($costRequest->fresh());

        return response()->json([
            'message' => 'Cost request reviewed successfully.',
            'data' => CostRequestResource::make($costRequest->fresh()->load(['requester', 'reviewer'])),
        ]);
    }

    private function authorizeTicketAccess(User $user, Ticket $ticket): void
    {
        if ($user->hasRole([User::ROLE_ADMIN, User::ROLE_OPERATIONS_MANAGER])) {
            return;
        }

        if (
            $user->hasRole(User::ROLE_TECHNICIAN)
            && (int) $ticket->assigned_to === (int) $user->id
            && in_array($ticket->status, $this->technicianVisibleStatuses(), true)
        ) {
            return;
        }

        if ($this->isReporterScopedRole($user) && (int) $ticket->reported_by === (int) $user->id) {
            return;
        }

        abort(403, 'You do not have access to this ticket.');
    }

    private function technicianVisibleStatuses(): array
    {
        return ['assigned', 'in_progress', 'on_hold', 'completed', 'closed', 'overdue'];
    }

    private function isReporterScopedRole(User $user): bool
    {
        return $user->hasRole([
            User::ROLE_TENANT,
            User::ROLE_MANAGING_DIRECTOR,
            User::ROLE_GENERAL_MANAGER,
        ]);
    }

    private function mustGoThroughOperationsApproval(User $user): bool
    {
        return $user->hasRole([
            User::ROLE_MANAGING_DIRECTOR,
            User::ROLE_GENERAL_MANAGER,
        ]);
    }

    private function canCreateTickets(User $user): bool
    {
        return $user->hasRole([
            User::ROLE_TENANT,
            User::ROLE_ADMIN,
            User::ROLE_OPERATIONS_MANAGER,
            User::ROLE_MANAGING_DIRECTOR,
            User::ROLE_GENERAL_MANAGER,
        ]);
    }
}
