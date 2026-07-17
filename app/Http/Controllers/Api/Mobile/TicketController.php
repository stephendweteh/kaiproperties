<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CostRequestReviewRequest;
use App\Http\Requests\Api\CostRequestStoreRequest;
use App\Http\Requests\Api\Mobile\TicketPhaseRequest;
use App\Http\Requests\Api\TicketAssignRequest;
use App\Http\Requests\Api\TicketStatusRequest;
use App\Http\Requests\Api\TicketStoreRequest;
use App\Http\Resources\CostRequestResource;
use App\Http\Resources\PhaseAttachmentResource;
use App\Http\Resources\TicketAttachmentResource;
use App\Http\Resources\TicketPhaseResource;
use App\Http\Resources\TicketResource;
use App\Models\CostRequest;
use App\Models\PhaseAttachment;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketPhase;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    // -----------------------------------------------------------------------
    // Tickets
    // -----------------------------------------------------------------------

    public function index(Request $request)
    {
        $user = $request->user();
        $isReporterScopedRole = $this->isReporterScopedRole($user);

        $query = Ticket::query()
            ->with(['property:id,name,code', 'category:id,name', 'reporter:id,name', 'technician:id,name'])
            ->when($request->filled('status'), fn (Builder $b) => $b->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn (Builder $b) => $b->where('priority', $request->string('priority')))
            ->when($request->filled('property_id'), fn (Builder $b) => $b->where('property_id', $request->integer('property_id')))
            ->when($request->filled('maintenance_category_id'), fn (Builder $b) => $b->where('maintenance_category_id', $request->integer('maintenance_category_id')))
            ->when($request->filled('assigned_to'), fn (Builder $b) => $b->where('assigned_to', $request->integer('assigned_to')))
            ->when($request->filled('search'), function (Builder $b) use ($request): void {
                $search = $request->string('search');
                $b->where(fn (Builder $inner) => $inner
                    ->where('ticket_no', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                );
            })
            ->latest();

        if ($isReporterScopedRole && ! $this->hasFullTicketVisibility($user)) {
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
        $user = $request->user();

        if (! $this->canCreateTickets($user)) {
            return response()->json(['message' => 'You do not have permission to log tickets.'], 403);
        }

        $validated = $request->validated();

        if (empty($validated['estimated_cost'])) {
            $validated['estimated_cost_currency'] = null;
        }

        $status = $this->mustGoThroughOperationsApproval($user) ? 'pending_approval' : 'logged';

        $ticket = Ticket::create([
            ...$validated,
            'reported_by' => $user->id,
            'status'      => $status,
            'priority'    => $validated['priority'] ?? 'medium',
        ]);

        $forNotification = $ticket->fresh(['reporter', 'technician']);
        app()->terminating(function () use ($forNotification): void {
            $this->notificationService->sendTicketLogged($forNotification);
        });

        return response()->json([
            'message' => $status === 'pending_approval'
                ? 'Ticket created and sent for approval.'
                : 'Ticket created successfully.',
            'data'    => TicketResource::make($ticket->load(['property', 'category', 'reporter'])),
        ], 201);
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

    public function update(TicketStoreRequest $request, Ticket $ticket)
    {
        $user = $request->user();

        if (! $this->canEditTicket($user, $ticket)) {
            return response()->json(['message' => 'You do not have permission to edit this ticket.'], 403);
        }

        $validated = $request->validated();

        if (empty($validated['estimated_cost'])) {
            $validated['estimated_cost_currency'] = null;
        }

        $ticket->update($validated);

        return response()->json([
            'message' => 'Ticket updated successfully.',
            'data' => TicketResource::make($ticket->fresh()->load(['property', 'category', 'reporter', 'technician'])),
        ]);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->authorizeAccess($request->user(), $ticket);

        return response()->json([
            'data' => TicketResource::make($ticket->load([
                'property',
                'category',
                'reporter:id,name,email',
                'technician:id,name,email',
                'costRequests.requester:id,name,email',
                'costRequests.reviewer:id,name,email',
                'attachments.uploader:id,name',
                'phases.attachments.uploader:id,name',
            ])),
        ]);
    }

    public function changeStatus(TicketStatusRequest $request, Ticket $ticket)
    {
        $user = $request->user();
        $previousStatus = $ticket->status;

        if ($user->hasRole(User::ROLE_TECHNICIAN) && (int) $ticket->assigned_to !== (int) $user->id) {
            return response()->json(['message' => 'You can only update your assigned tickets.'], 403);
        }

        if ($user->hasRole(User::ROLE_TENANT)) {
            return response()->json(['message' => 'Tenants cannot change ticket status.'], 403);
        }

        if ($user->hasRole(User::ROLE_TECHNICIAN) && ! in_array($request->string('status')->toString(), ['assigned', 'in_progress', 'completed'], true)) {
            return response()->json(['message' => 'Technicians can only move assigned work to in progress or completed.'], 403);
        }

        if (
            $user->hasRole(User::ROLE_OPERATIONS_MANAGER)
            && in_array($request->string('status')->toString(), ['logged', 'on_hold'], true)
            && empty($ticket->assigned_to)
        ) {
            return response()->json(['message' => 'Assign a technician before approving or placing this ticket on hold.'], 422);
        }

        $validated   = $request->validated();
        $attributes  = ['status' => $validated['status']];

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
            $fresh = $ticket->fresh();
            app()->terminating(function () use ($fresh, $previousStatus): void {
                $this->notificationService->sendTicketStatusChanged($fresh, $previousStatus);
            });
        }

        return response()->json([
            'message' => 'Ticket status updated.',
            'data'    => TicketResource::make($ticket->fresh()->load(['property', 'category', 'reporter', 'technician'])),
        ]);
    }

    // -----------------------------------------------------------------------
    // Attachments
    // -----------------------------------------------------------------------

    public function uploadAttachment(Request $request, Ticket $ticket)
    {
        $this->authorizeAccess($request->user(), $ticket);

        $request->validate([
            'file'            => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:20480'],
            'attachment_type' => ['nullable', 'in:image,document'],
        ]);

        $file   = $request->file('file');
        $mime   = $file->getMimeType();
        $type   = $request->input('attachment_type', str_starts_with($mime, 'image/') ? 'image' : 'document');

        $path = $file->store('ticket-attachments/'.$ticket->id, 'public');

        $attachment = TicketAttachment::create([
            'ticket_id'       => $ticket->id,
            'uploaded_by'     => $request->user()->id,
            'file_path'       => $path,
            'file_name'       => $file->getClientOriginalName(),
            'mime_type'       => $mime,
            'file_size'       => $file->getSize(),
            'attachment_type' => $type,
        ]);

        return response()->json([
            'message' => 'Attachment uploaded.',
            'data'    => TicketAttachmentResource::make($attachment->load('uploader')),
        ], 201);
    }

    // -----------------------------------------------------------------------
    // Ticket Phases
    // -----------------------------------------------------------------------

    public function phases(Request $request, Ticket $ticket)
    {
        $this->authorizeAccess($request->user(), $ticket);

        $phases = $ticket->phases()->with('attachments.uploader:id,name')->orderBy('phase_number')->get();

        return response()->json([
            'data' => TicketPhaseResource::collection($phases),
        ]);
    }

    public function addPhase(TicketPhaseRequest $request, Ticket $ticket)
    {
        $user = $request->user();

        if (! $user->hasRole([User::ROLE_TECHNICIAN, User::ROLE_OPERATIONS_MANAGER, User::ROLE_ADMIN])) {
            return response()->json(['message' => 'You do not have permission to add phases.'], 403);
        }

        if ($user->hasRole(User::ROLE_TECHNICIAN) && (int) $ticket->assigned_to !== (int) $user->id) {
            return response()->json(['message' => 'You can only add phases to your assigned ticket.'], 403);
        }

        $lastPhase   = $ticket->phases()->max('phase_number') ?? 0;
        $validated   = $request->validated();

        $phase = TicketPhase::create([
            'ticket_id'        => $ticket->id,
            'phase_name'       => $validated['phase_name'],
            'phase_number'     => $lastPhase + 1,
            'description'      => $validated['description'] ?? null,
            'technician_notes' => $validated['technician_notes'] ?? null,
            'status'           => 'in_progress',
            'started_at'       => now(),
        ]);

        return response()->json([
            'message' => 'Phase added.',
            'data'    => TicketPhaseResource::make($phase),
        ], 201);
    }

    public function completePhase(Request $request, Ticket $ticket, TicketPhase $phase)
    {
        $user = $request->user();

        if ($phase->ticket_id !== $ticket->id) {
            return response()->json(['message' => 'Phase does not belong to this ticket.'], 404);
        }

        if ($user->hasRole(User::ROLE_TECHNICIAN) && (int) $ticket->assigned_to !== (int) $user->id) {
            return response()->json(['message' => 'You can only update phases on your assigned ticket.'], 403);
        }

        $phase->markAsCompleted();

        return response()->json([
            'message' => 'Phase marked as completed.',
            'data'    => TicketPhaseResource::make($phase->fresh()),
        ]);
    }

    public function updatePhase(Request $request, Ticket $ticket, TicketPhase $phase)
    {
        $user = $request->user();

        if ($phase->ticket_id !== $ticket->id) {
            return response()->json(['message' => 'Phase does not belong to this ticket.'], 404);
        }

        if (! $user->hasRole([User::ROLE_TECHNICIAN, User::ROLE_OPERATIONS_MANAGER, User::ROLE_ADMIN])) {
            return response()->json(['message' => 'You do not have permission to update phases.'], 403);
        }

        if ($user->hasRole(User::ROLE_TECHNICIAN) && (int) $ticket->assigned_to !== (int) $user->id) {
            return response()->json(['message' => 'You can only update phases on your assigned ticket.'], 403);
        }

        $validated = $request->validate([
            'technician_notes' => ['nullable', 'string'],
            'manager_notes' => ['nullable', 'string'],
        ]);

        if ($user->hasRole(User::ROLE_TECHNICIAN) && array_key_exists('manager_notes', $validated)) {
            return response()->json(['message' => 'Technicians cannot update manager comments.'], 403);
        }

        $attributes = [];

        if (array_key_exists('technician_notes', $validated)) {
            $attributes['technician_notes'] = $validated['technician_notes'];
        }

        if (array_key_exists('manager_notes', $validated)) {
            $attributes['manager_notes'] = $validated['manager_notes'];
        }

        if (empty($attributes)) {
            return response()->json(['message' => 'No phase fields provided for update.'], 422);
        }

        $phase->update($attributes);

        return response()->json([
            'message' => 'Phase updated successfully.',
            'data' => TicketPhaseResource::make($phase->fresh()->load('attachments.uploader:id,name')),
        ]);
    }

    public function uploadPhaseAttachment(Request $request, Ticket $ticket, TicketPhase $phase)
    {
        $user = $request->user();

        if ($phase->ticket_id !== $ticket->id) {
            return response()->json(['message' => 'Phase does not belong to this ticket.'], 404);
        }

        if ($user->hasRole(User::ROLE_TECHNICIAN) && (int) $ticket->assigned_to !== (int) $user->id) {
            return response()->json(['message' => 'You can only upload to your assigned ticket phases.'], 403);
        }

        $request->validate([
            'file'            => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:20480'],
            'attachment_type' => ['nullable', 'in:image,document'],
        ]);

        $file = $request->file('file');
        $mime = $file->getMimeType();
        $type = $request->input('attachment_type', str_starts_with($mime, 'image/') ? 'image' : 'document');

        $path = $file->store('phase-attachments/'.$phase->id, 'public');

        $attachment = PhaseAttachment::create([
            'ticket_phase_id' => $phase->id,
            'uploaded_by'     => $user->id,
            'file_path'       => $path,
            'file_name'       => $file->getClientOriginalName(),
            'mime_type'       => $mime,
            'file_size'       => $file->getSize(),
            'attachment_type' => $type,
        ]);

        return response()->json([
            'message' => 'Phase attachment uploaded.',
            'data'    => PhaseAttachmentResource::make($attachment->load('uploader')),
        ], 201);
    }

    // -----------------------------------------------------------------------
    // Cost Requests
    // -----------------------------------------------------------------------

    public function submitCostRequest(CostRequestStoreRequest $request, Ticket $ticket)
    {
        $user = $request->user();

        if (! $user->hasRole(User::ROLE_TECHNICIAN) || (int) $ticket->assigned_to !== (int) $user->id) {
            return response()->json(['message' => 'Only the assigned technician can submit a cost request.'], 403);
        }

        $validated    = $request->validated();
        $prevStatus   = $ticket->status;

        $costRequest = CostRequest::create([
            'ticket_id'    => $ticket->id,
            'requested_by' => $user->id,
            'amount'       => $validated['amount'],
            'reason'       => $validated['reason'],
            'status'       => 'pending',
        ]);

        $ticket->update([
            'status'                  => 'pending_approval',
            'requires_additional_cost'=> true,
        ]);

        if ($prevStatus !== $ticket->status) {
            $fresh = $ticket->fresh();
            app()->terminating(function () use ($fresh, $prevStatus): void {
                $this->notificationService->sendTicketStatusChanged($fresh, $prevStatus);
            });
        }

        return response()->json([
            'message' => 'Cost request submitted for approval.',
            'data'    => CostRequestResource::make($costRequest->load(['requester', 'reviewer'])),
        ], 201);
    }

    public function reviewCostRequest(CostRequestReviewRequest $request, CostRequest $costRequest)
    {
        $user = $request->user();

        if (! $user->hasRole([User::ROLE_OPERATIONS_MANAGER, User::ROLE_ADMIN])) {
            return response()->json(['message' => 'Only operations managers or admins can review cost requests.'], 403);
        }

        $validated  = $request->validated();
        $prevStatus = $costRequest->ticket->status;

        $costRequest->update([
            'status'           => $validated['status'],
            'reviewed_by'      => $user->id,
            'reviewed_at'      => now(),
            'reviewer_comment' => $validated['reviewer_comment'] ?? null,
        ]);

        $nextStatus = $validated['status'] === 'approved' ? 'in_progress' : 'on_hold';
        $costRequest->ticket()->update(['status' => $nextStatus]);

        if ($prevStatus !== $nextStatus) {
            $fresh = $costRequest->ticket->fresh();
            app()->terminating(function () use ($fresh, $prevStatus): void {
                $this->notificationService->sendTicketStatusChanged($fresh, $prevStatus);
            });
        }

        $this->notificationService->sendCostRequestReviewed($costRequest->fresh());

        return response()->json([
            'message' => 'Cost request reviewed.',
            'data'    => CostRequestResource::make($costRequest->fresh()->load(['requester', 'reviewer'])),
        ]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function authorizeAccess(User $user, Ticket $ticket): void
    {
        if ($this->hasFullTicketVisibility($user) || $user->hasRole([User::ROLE_ADMIN, User::ROLE_OPERATIONS_MANAGER])) {
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

    private function hasFullTicketVisibility(User $user): bool
    {
        return $user->hasRole([
            User::ROLE_ADMIN,
            User::ROLE_OPERATIONS_MANAGER,
            User::ROLE_MANAGING_DIRECTOR,
            User::ROLE_GENERAL_MANAGER,
        ]);
    }

    private function canEditTicket(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole([User::ROLE_ADMIN, User::ROLE_OPERATIONS_MANAGER])) {
            return true;
        }

        if (
            $this->isReporterScopedRole($user)
            && (int) $ticket->reported_by === (int) $user->id
            && in_array($ticket->status, ['pending_approval', 'logged', 'rejected'], true)
        ) {
            return true;
        }

        return false;
    }
}
