<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MaintenanceCategory;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $isReporterScopedRole = $user ? $this->isReporterScopedRole($user) : false;
        $isTechnician = $user?->hasRole(User::ROLE_TECHNICIAN) ?? false;
        $canApproveTickets = $user ? $this->canApproveTickets($user) : false;
        $canEditTickets = $user ? $this->canEditTickets($user) : false;
        $reviewMode = $canApproveTickets && ! $canEditTickets;
        $canCreateTickets = $user ? $this->canCreateTickets($user) : false;

        $tickets = Ticket::query()
            ->with([
                'property',
                'category',
                'reporter:id,name',
                'technician:id,name',
                'attachments:id,ticket_id,file_path,file_name,attachment_type',
            ])
            ->when(
                $isReporterScopedRole && ! $this->hasFullTicketVisibility($user),
                fn (Builder $builder) => $builder->where('reported_by', $request->user()->id)
            )
            ->when($isTechnician, fn (Builder $builder) => $builder
                ->where('assigned_to', $request->user()->id)
                ->whereIn('status', $this->technicianVisibleStatuses()))
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
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'statuses' => Ticket::STATUSES,
            'properties' => Property::orderBy('name')->get(),
            'categories' => MaintenanceCategory::orderBy('name')->get(),
            'isTechnician' => $isTechnician,
            'canApproveTickets' => $canApproveTickets,
            'canEditTickets' => $canEditTickets,
            'canCreateTickets' => $canCreateTickets,
            'reviewMode' => $reviewMode,
            'isOperationsManager' => $user?->hasRole(User::ROLE_OPERATIONS_MANAGER) ?? false,
            'technicians' => ($isReporterScopedRole || $isTechnician)
                ? collect()
                : User::where('role', User::ROLE_TECHNICIAN)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $user = request()->user();
        $isTenant = $user ? $this->isReporterScopedRole($user) : false;
        $canCreateTickets = $user ? $this->canCreateTickets($user) : false;

        abort_unless($canCreateTickets, 403);

        return view('tickets.create', [
            'isTenant' => $isTenant,
            'customers' => Customer::with('properties')->where('is_active', true)->orderBy('name')->get(),
            'properties' => Property::where('is_active', true)->orderBy('name')->get(),
            'categories' => MaintenanceCategory::where('is_active', true)->orderBy('name')->get(),
            'priorities' => Ticket::PRIORITIES,
               'estimatedCostCurrencies' => Ticket::ESTIMATED_COST_CURRENCIES,
            'reporters' => $isTenant ? collect() : User::orderBy('name')->get(),
            'technicians' => $isTenant
                ? collect()
                : User::where('role', User::ROLE_TECHNICIAN)->orderBy('name')->get(),
        ]);
    }

    public function show(Ticket $ticket)
    {
        $user = request()->user();
        $canApproveTickets = $this->canApproveTickets($user);

        abort_unless($this->canViewTicket($user, $ticket), 403);

        $ticket->load([
            'property',
            'category',
            'reporter:id,name',
            'technician:id,name',
            'attachments.uploader:id,name',
            'phases.attachments.uploader:id,name',
        ]);

        return view('tickets.show', [
            'ticket' => $ticket,
            'canEditTickets' => $this->canEditTickets($user),
            'canApproveTickets' => $canApproveTickets,
            'canTechnicianUpdate' => $this->canTechnicianUpdateStatus($user, $ticket),
            'isTechnician' => $user->hasRole(User::ROLE_TECHNICIAN),
            'isOperationsManager' => $user->hasRole(User::ROLE_OPERATIONS_MANAGER),
            'canViewWorkProgress' => $user->hasRole([
                User::ROLE_ADMIN,
                User::ROLE_OPERATIONS_MANAGER,
                User::ROLE_MANAGING_DIRECTOR,
                User::ROLE_GENERAL_MANAGER,
                User::ROLE_TECHNICIAN,
            ]),
            'technicians' => $canApproveTickets
                ? User::where('role', User::ROLE_TECHNICIAN)->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $isTenant = $user ? $this->isReporterScopedRole($user) : false;

        abort_unless($user && $this->canCreateTickets($user), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'property_id' => ['required', 'exists:properties,id'],
            'maintenance_category_id' => ['required', 'exists:maintenance_categories,id'],
            'unit' => ['nullable', 'string', 'max:100'],
            'reported_by' => $isTenant ? ['nullable'] : ['required', 'exists:users,id'],
            'assigned_to' => $isTenant ? ['nullable'] : ['nullable', 'exists:users,id'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'etd' => ['nullable', 'date'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'image_attachments' => ['nullable', 'array', 'max:8'],
            'image_attachments.*' => ['file', 'image', 'max:5120'],
            'camera_attachment' => ['nullable', 'image', 'max:5120'],
            'document_attachments' => ['nullable', 'array', 'max:8'],
            'document_attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,txt,csv', 'max:10240'],
        ]);

        if ($isTenant) {
            $validated['reported_by'] = $request->user()->id;
            $validated['assigned_to'] = null;
        }

        if (empty($validated['estimated_cost'])) {
            $validated['estimated_cost_currency'] = null;
        }

        $status = $this->mustGoThroughOperationsApproval($user) ? 'pending_approval' : 'logged';

        $ticket = Ticket::create([
            ...$validated,
            'status' => $status,
        ]);

        $this->storeAttachments($request, $ticket);

        $ticketForNotification = $ticket->fresh(['reporter', 'technician']);
        app()->terminating(function () use ($ticketForNotification): void {
            $this->notificationService->sendTicketLogged($ticketForNotification);
        });

        $redirect = $isTenant ? route('tickets.show', $ticket) : route('tickets.index');

        return redirect($redirect)
            ->with('success', $status === 'pending_approval'
                ? 'Ticket created and sent for approval.'
                : 'Ticket created successfully.');
    }

    public function edit(Ticket $ticket)
    {
        $user = request()->user();
        $technicianMode = $this->canTechnicianUpdateStatus($user, $ticket);

        abort_unless($this->canEditTickets($user) || $this->canApproveTickets($user) || $technicianMode, 403);

        $reviewMode = $this->canApproveTickets($user) && ! $this->canEditTickets($user);

        $ticket->load(['reporter:id,name', 'technician:id,name', 'attachments.uploader:id,name', 'phases.attachments.uploader:id,name']);

        return view('tickets.edit', [
            'ticket' => $ticket,
            'properties' => Property::where('is_active', true)->orderBy('name')->get(),
            'categories' => MaintenanceCategory::where('is_active', true)->orderBy('name')->get(),
            'priorities' => Ticket::PRIORITIES,
               'estimatedCostCurrencies' => Ticket::ESTIMATED_COST_CURRENCIES,
            'statuses' => $technicianMode ? ['in_progress', 'completed'] : Ticket::STATUSES,
            'technicianMode' => $technicianMode,
            'reviewMode' => $reviewMode,
            'isOperationsManager' => $user->hasRole(User::ROLE_OPERATIONS_MANAGER),
            'technicians' => User::where('role', User::ROLE_TECHNICIAN)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $canEditTickets = $this->canEditTickets($user);
        $canApproveTickets = $this->canApproveTickets($user);
        $canTechnicianUpdate = $this->canTechnicianUpdateStatus($user, $ticket);
        $previousStatus = $ticket->status;
        $previousAssignedTo = (int) ($ticket->assigned_to ?? 0);

        abort_unless($canEditTickets || $canApproveTickets || $canTechnicianUpdate || $user->hasRole(User::ROLE_OPERATIONS_MANAGER), 403);

        $isOperationsManager = $user->hasRole(User::ROLE_OPERATIONS_MANAGER);

        if ($canTechnicianUpdate || $isOperationsManager) {
            $action = $request->input('action', 'update_status');

            if ($isOperationsManager && $action === 'save_phase_comment') {
                $validated = $request->validate([
                    'phase_id' => ['required', 'exists:ticket_phases,id'],
                    'manager_notes' => ['required', 'string'],
                ]);

                $phase = $ticket->phases()
                    ->whereKey($validated['phase_id'])
                    ->first();

                if (! $phase) {
                    return back()->withErrors(['phase_id' => 'Selected phase does not belong to this ticket.'])->withInput();
                }

                $existingNotes = trim((string) ($phase->manager_notes ?? ''));
                $newNotes = trim($validated['manager_notes']);
                $timestamp = now()->format('Y-m-d H:i');
                $managerName = $user->name;
                $entry = "[{$managerName} {$timestamp}] {$newNotes}";

                $phase->update([
                    'manager_notes' => $existingNotes !== ''
                        ? $existingNotes.PHP_EOL.PHP_EOL.$entry
                        : $entry,
                ]);

                return redirect()
                    ->route('tickets.show', $ticket)
                    ->with('success', 'Phase comment added successfully.');
            }

            // Operations Manager marks ticket as completed
            if ($action === 'mark_completed') {
                if (! $ticket->started_at) {
                    $ticket->started_at = now();
                }
                $ticket->status = 'completed';
                $ticket->completed_at = now();
                $ticket->save();

                $ticketForNotification = $ticket->fresh();
                app()->terminating(function () use ($ticketForNotification, $previousStatus): void {
                    $this->notificationService->sendTicketStatusChanged($ticketForNotification, $previousStatus);
                });

                return redirect()
                    ->route('tickets.index')
                    ->with('success', 'Ticket marked as completed.');
            }

            // Handle phase-based updates
            if (in_array($action, ['save_phase', 'complete_phase'])) {
                $validated = $request->validate([
                    'technician_notes' => ['nullable', 'string'],
                    'phase_image' => ['nullable', 'image', 'max:5120'],
                    'phase_document' => ['nullable', 'file', 'max:10240'],
                ]);

                // Get or create current phase
                $currentPhase = $ticket->phases()
                    ->where('status', 'in_progress')
                    ->first();

                if (!$currentPhase) {
                    $nextPhaseNumber = $ticket->phases()->max('phase_number') ?? 0;
                    $nextPhaseNumber++;

                    $currentPhase = $ticket->phases()->create([
                        'phase_name' => "Phase {$nextPhaseNumber}",
                        'phase_number' => $nextPhaseNumber,
                        'status' => 'in_progress',
                        'started_at' => now(),
                    ]);
                }

                // Update phase notes
                if ($validated['technician_notes']) {
                    $currentPhase->update(['technician_notes' => $validated['technician_notes']]);
                }

                // Handle file uploads
                if ($request->hasFile('phase_image')) {
                    $file = $request->file('phase_image');
                    $path = $file->store('ticket-phases', 'public');

                    $currentPhase->attachments()->create([
                        'uploaded_by' => $user->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'attachment_type' => 'image',
                    ]);
                }

                if ($request->hasFile('phase_document')) {
                    $file = $request->file('phase_document');
                    $path = $file->store('ticket-phases', 'public');

                    $currentPhase->attachments()->create([
                        'uploaded_by' => $user->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'attachment_type' => 'document',
                    ]);
                }

                // If completing phase, mark it as completed
                if ($action === 'complete_phase') {
                    $currentPhase->markAsCompleted();
                    $ticket->current_phase = $currentPhase->phase_number + 1;
                    $ticket->status = 'in_progress';
                } else {
                    $ticket->status = 'in_progress';
                }

                $ticket->save();

                $redirectRoute = $isOperationsManager ? route('tickets.show', $ticket) : route('tickets.edit', $ticket);
                
                return redirect($redirectRoute)
                    ->with('success', 'Phase saved successfully.');
            }

            // Standard status update (non-phase) remains technician-only in this branch.
            // Operations manager updates without a phase action should fall through
            // to the general ticket update logic below.
            if (!$isOperationsManager) {
                $validated = $request->validate([
                           'estimated_cost_currency' => ['nullable', 'in:GBP,USD,EUR,GHS,CNY', 'required_with:estimated_cost'],
                    'status' => ['required', 'in:in_progress,completed'],
                ]);

                if ($validated['status'] === 'in_progress' && ! $ticket->started_at) {
                    $ticket->started_at = now();
                }

                if ($validated['status'] === 'completed') {
                    if (! $ticket->started_at) {
                        $ticket->started_at = now();
                    }

                    $ticket->completed_at = now();
                }

                $ticket->status = $validated['status'];
                $ticket->save();

                if ($previousStatus !== $ticket->status) {
                    $ticketForNotification = $ticket->fresh();
                    app()->terminating(function () use ($ticketForNotification, $previousStatus): void {
                        $this->notificationService->sendTicketStatusChanged($ticketForNotification, $previousStatus);
                    });
                }

                return redirect()
                    ->route('tickets.index')
                    ->with('success', 'Ticket status updated successfully.');
            }
        }

        if ($canApproveTickets && ! $canEditTickets) {
            $validated = $request->validate([
                'assigned_to' => ['nullable', 'exists:users,id'],
                'status' => ['required', 'in:logged,on_hold'],
            ]);

            $assignedTo = ! empty($validated['assigned_to'])
                ? (int) $validated['assigned_to']
                : (int) ($ticket->assigned_to ?? 0);

            if ($assignedTo > 0) {
                $technician = User::where('id', $assignedTo)
                    ->where('role', User::ROLE_TECHNICIAN)
                    ->first();

                if (! $technician) {
                    return back()->withErrors(['assigned_to' => 'Selected user is not a technician.'])->withInput();
                }
            }

            if ($assignedTo === 0) {
                return back()->withErrors(['assigned_to' => 'Assign a technician before approving or placing this ticket on hold.'])->withInput();
            }

            $attributes = [
                'assigned_to' => $assignedTo > 0 ? $assignedTo : null,
                'status' => $validated['status'],
            ];

            if ($attributes['status'] === 'in_progress' && ! $ticket->started_at) {
                $attributes['started_at'] = now();
            }

            if ($attributes['status'] === 'completed') {
                $attributes['completed_at'] = now();
            }

            if ($attributes['status'] === 'closed') {
                $attributes['closed_at'] = now();
            }

            $ticket->update($attributes);

            $ticket->refresh();

            if ((int) ($ticket->assigned_to ?? 0) !== $previousAssignedTo || $previousStatus !== $ticket->status) {
                $ticketForNotification = $ticket->fresh();
                app()->terminating(function () use ($ticketForNotification): void {
                    $this->notificationService->sendTicketAssigned($ticketForNotification);
                });
            }

            if ($previousStatus !== $ticket->status) {
                $ticketForNotification = $ticket->fresh(['reporter', 'technician']);
                app()->terminating(function () use ($ticketForNotification, $previousStatus): void {
                    $this->notificationService->sendTicketStatusChanged($ticketForNotification, $previousStatus);
                });
            }

            return redirect()
                ->route('tickets.index')
                ->with('success', 'Ticket reviewed successfully.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'property_id' => ['required', 'exists:properties,id'],
            'maintenance_category_id' => ['required', 'exists:maintenance_categories,id'],
            'unit' => ['nullable', 'string', 'max:100'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'status' => ['required', 'in:logged,assigned,in_progress,pending_approval,on_hold,completed,closed,rejected,overdue'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'etd' => ['nullable', 'date'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'estimated_cost_currency' => ['nullable', 'in:GBP,USD,EUR,GHS,CNY', 'required_with:estimated_cost'],
            'image_attachments' => ['nullable', 'array', 'max:8'],
            'image_attachments.*' => ['file', 'image', 'max:5120'],
            'camera_attachment' => ['nullable', 'image', 'max:5120'],
            'document_attachments' => ['nullable', 'array', 'max:8'],
            'document_attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,txt,csv', 'max:10240'],
            'remove_attachment_ids' => ['nullable', 'array'],
            'remove_attachment_ids.*' => ['integer'],
        ]);

        if (! $validated['assigned_to'] && $validated['status'] === 'assigned') {
            $validated['status'] = 'logged';
        }

        if ($validated['status'] === 'in_progress' && ! $ticket->started_at) {
            $validated['started_at'] = now();
        }

        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        if ($validated['status'] === 'closed') {
            $validated['closed_at'] = now();
        }

        if (empty($validated['estimated_cost'])) {
            $validated['estimated_cost_currency'] = null;
        }

        $ticket->update($validated);

        $this->removeSelectedAttachments($request, $ticket);
        $this->storeAttachments($request, $ticket);

        $ticket->refresh();

        if ((int) ($ticket->assigned_to ?? 0) !== $previousAssignedTo && ! empty($ticket->assigned_to)) {
            $ticketForNotification = $ticket->fresh();
            app()->terminating(function () use ($ticketForNotification): void {
                $this->notificationService->sendTicketAssigned($ticketForNotification);
            });
        }

        if ($previousStatus !== $ticket->status) {
            $ticketForNotification = $ticket->fresh(['reporter', 'technician']);
            app()->terminating(function () use ($ticketForNotification, $previousStatus): void {
                $this->notificationService->sendTicketStatusChanged($ticketForNotification, $previousStatus);
            });
        }

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Ticket updated successfully.');
    }

    public function destroy(Ticket $ticket)
    {
        $user = request()->user();

        abort_unless($this->canEditTickets($user), 403);

        $ticket->loadMissing('attachments:id,ticket_id,file_path');

        foreach ($ticket->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $ticket->delete();

        $this->notificationService->sendTicketDeleted($ticket);

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Ticket deleted successfully.');
    }

    public function review(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $canApproveTickets = $this->canApproveTickets($user);
        $canEditTickets = $this->canEditTickets($user);
        $expectsJson = $request->expectsJson();
        $previousStatus = $ticket->status;
        $previousAssignedTo = (int) ($ticket->assigned_to ?? 0);

        abort_unless($canApproveTickets, 403);

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
            'decision' => ['required', 'in:approve,hold'],
        ]);

        $assignedTo = ! empty($validated['assigned_to'])
            ? (int) $validated['assigned_to']
            : (int) ($ticket->assigned_to ?? 0);

        if ($assignedTo > 0) {
            $technician = User::where('id', $assignedTo)
                ->where('role', User::ROLE_TECHNICIAN)
                ->first();

            if (! $technician) {
                if ($expectsJson) {
                    return response()->json([
                        'message' => 'Selected user is not a technician.',
                    ], 422);
                }

                return back()->withErrors(['assigned_to' => 'Selected user is not a technician.'])->withInput();
            }
        }

        if ($assignedTo === 0) {
            if ($expectsJson) {
                return response()->json([
                    'message' => 'Assign a technician before approving or placing this ticket on hold.',
                ], 422);
            }

            return back()->withErrors(['decision' => 'Assign a technician before approving or placing this ticket on hold.'])->withInput();
        }

        $newStatus = $validated['decision'] === 'approve' ? 'logged' : 'on_hold';

        $ticket->update([
            'assigned_to' => $assignedTo,
            'status' => $newStatus,
        ]);

        $ticket->refresh();

        if ((int) ($ticket->assigned_to ?? 0) !== $previousAssignedTo) {
            $ticketForNotification = $ticket->fresh();
            app()->terminating(function () use ($ticketForNotification): void {
                $this->notificationService->sendTicketAssigned($ticketForNotification);
            });
        }

        if ($previousStatus !== $ticket->status) {
            $ticketForNotification = $ticket->fresh(['reporter', 'technician']);
            app()->terminating(function () use ($ticketForNotification, $previousStatus): void {
                $this->notificationService->sendTicketStatusChanged($ticketForNotification, $previousStatus);
            });
        }

        if ($expectsJson) {
            return response()->json([
                'message' => 'Ticket review action applied.',
                'data' => [
                    'ticket_id' => $ticket->id,
                    'status' => $ticket->status,
                    'status_label' => $ticket->status === 'logged'
                        ? 'Logged/New'
                        : str($ticket->status)->replace('_', ' ')->title()->toString(),
                ],
            ]);
        }

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Ticket review action applied.');
    }

    private function storeAttachments(Request $request, Ticket $ticket): void
    {
        $imageFiles = $request->file('image_attachments', []);
        $documentFiles = $request->file('document_attachments', []);
        $cameraFile = $request->file('camera_attachment');

        if ($cameraFile instanceof UploadedFile) {
            $imageFiles[] = $cameraFile;
        }

        foreach ($imageFiles as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $this->persistAttachment($ticket, $file, 'image', $request->user()?->id);
        }

        foreach ($documentFiles as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $this->persistAttachment($ticket, $file, 'document', $request->user()?->id);
        }
    }

    private function persistAttachment(Ticket $ticket, UploadedFile $file, string $type, ?int $uploaderId): void
    {
        $path = $file->store("tickets/{$ticket->id}/attachments", 'public');

        $ticket->attachments()->create([
            'uploaded_by' => $uploaderId,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => (int) $file->getSize(),
            'attachment_type' => $type,
        ]);
    }

    private function removeSelectedAttachments(Request $request, Ticket $ticket): void
    {
        $ids = collect($request->input('remove_attachment_ids', []))
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $attachments = $ticket->attachments()->whereIn('id', $ids->all())->get();

        /** @var TicketAttachment $attachment */
        foreach ($attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();
        }
    }

    private function canApproveTickets(?User $user): bool
    {
        return (bool) $user?->hasRole(User::ROLE_OPERATIONS_MANAGER);
    }

    private function canEditTickets(?User $user): bool
    {
        return (bool) $user?->hasRole([
            User::ROLE_ADMIN,
            User::ROLE_OPERATIONS_MANAGER,
        ]);
    }

    private function canCreateTickets(?User $user): bool
    {
        return (bool) $user?->hasRole([
            User::ROLE_ADMIN,
            User::ROLE_OPERATIONS_MANAGER,
            User::ROLE_MANAGING_DIRECTOR,
            User::ROLE_GENERAL_MANAGER,
        ]);
    }

    private function isReporterScopedRole(?User $user): bool
    {
        return (bool) $user?->hasRole([
            User::ROLE_TENANT,
            User::ROLE_MANAGING_DIRECTOR,
            User::ROLE_GENERAL_MANAGER,
        ]);
    }

    private function mustGoThroughOperationsApproval(?User $user): bool
    {
        return (bool) $user?->hasRole([
            User::ROLE_MANAGING_DIRECTOR,
            User::ROLE_GENERAL_MANAGER,
        ]);
    }

    private function canViewTicket(?User $user, Ticket $ticket): bool
    {
        if ($this->hasFullTicketVisibility($user) || $this->canEditTickets($user) || $this->canApproveTickets($user)) {
            return true;
        }

        if ($this->isReporterScopedRole($user) && (int) $ticket->reported_by === (int) $user->id) {
            return true;
        }

        return $this->canTechnicianWorkOnTicket($user, $ticket);
    }

    private function canTechnicianWorkOnTicket(?User $user, Ticket $ticket): bool
    {
        return (bool) (
            $user?->hasRole(User::ROLE_TECHNICIAN)
            && (int) $ticket->assigned_to === (int) $user->id
            && in_array($ticket->status, $this->technicianVisibleStatuses(), true)
        );
    }

    private function canTechnicianUpdateStatus(?User $user, Ticket $ticket): bool
    {
        return (bool) (
            $user?->hasRole(User::ROLE_TECHNICIAN)
            && (int) $ticket->assigned_to === (int) $user->id
            && in_array($ticket->status, ['logged', 'assigned', 'in_progress'], true)
        );
    }

    private function technicianVisibleStatuses(): array
    {
        return ['logged', 'assigned', 'in_progress', 'on_hold', 'completed', 'closed', 'overdue'];
    }

    private function hasFullTicketVisibility(?User $user): bool
    {
        return (bool) $user?->hasRole([
            User::ROLE_ADMIN,
            User::ROLE_OPERATIONS_MANAGER,
            User::ROLE_MANAGING_DIRECTOR,
            User::ROLE_GENERAL_MANAGER,
        ]);
    }
}
