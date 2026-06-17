<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceCategory;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = Ticket::query()
            ->with([
                'property',
                'category',
                'reporter:id,name',
                'technician:id,name',
                'attachments:id,ticket_id,file_path,file_name,attachment_type',
            ])
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
            'technicians' => User::where('role', User::ROLE_TECHNICIAN)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('tickets.create', [
            'properties' => Property::where('is_active', true)->orderBy('name')->get(),
            'categories' => MaintenanceCategory::where('is_active', true)->orderBy('name')->get(),
            'priorities' => Ticket::PRIORITIES,
            'reporters' => User::orderBy('name')->get(),
            'technicians' => User::where('role', User::ROLE_TECHNICIAN)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'property_id' => ['required', 'exists:properties,id'],
            'maintenance_category_id' => ['required', 'exists:maintenance_categories,id'],
            'unit' => ['nullable', 'string', 'max:100'],
            'reported_by' => ['required', 'exists:users,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'etd' => ['nullable', 'date'],
            'image_attachments' => ['nullable', 'array', 'max:8'],
            'image_attachments.*' => ['file', 'image', 'max:5120'],
            'camera_attachment' => ['nullable', 'image', 'max:5120'],
            'document_attachments' => ['nullable', 'array', 'max:8'],
            'document_attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,txt,csv', 'max:10240'],
        ]);

        $status = ! empty($validated['assigned_to'] ?? null) ? 'assigned' : 'logged';

        $ticket = Ticket::create([
            ...$validated,
            'status' => $status,
        ]);

        $this->storeAttachments($request, $ticket);

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Ticket created successfully.');
    }

    public function edit(Ticket $ticket)
    {
        $ticket->load(['attachments.uploader:id,name']);

        return view('tickets.edit', [
            'ticket' => $ticket,
            'properties' => Property::where('is_active', true)->orderBy('name')->get(),
            'categories' => MaintenanceCategory::where('is_active', true)->orderBy('name')->get(),
            'priorities' => Ticket::PRIORITIES,
            'statuses' => Ticket::STATUSES,
            'technicians' => User::where('role', User::ROLE_TECHNICIAN)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
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

        $ticket->update($validated);

        $this->removeSelectedAttachments($request, $ticket);
        $this->storeAttachments($request, $ticket);

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Ticket updated successfully.');
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
}
