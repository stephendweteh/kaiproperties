@extends('layouts.app', ['title' => 'View Ticket'])

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:1rem;">
        <div>
            <h2 style="margin-bottom:0.3rem;">Ticket {{ $ticket->ticket_no }}</h2>
            <p class="muted" style="margin:0;">{{ $canApproveTickets ? 'Review ticket details, assign a technician, then approve or hold.' : 'Read-only ticket details.' }}</p>
        </div>

        <div class="row-actions" style="display:flex; align-items:flex-end; gap:0.6rem; flex-wrap:wrap;">
            <a class="btn" href="{{ route('tickets.index') }}">Back to Tickets</a>
            @if($canEditTickets)
                <a class="btn btn-alt" href="{{ route('tickets.edit', $ticket) }}">Edit Ticket</a>
            @elseif($canApproveTickets)
                <form method="POST" action="{{ route('tickets.review', $ticket) }}" style="display:flex; align-items:flex-end; gap:0.45rem; flex-wrap:wrap;">
                    @csrf
                    <div style="min-width: 220px;">
                        <label for="assigned_to" class="muted" style="display:block; margin-bottom:0.25rem;">Assign Technician</label>
                        <select id="assigned_to" name="assigned_to" required>
                            <option value="">Select Technician</option>
                            @foreach(($technicians ?? collect()) as $technician)
                                <option value="{{ $technician->id }}" @selected((string) old('assigned_to', $ticket->assigned_to ?? '') === (string) $technician->id)>{{ $technician->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" name="decision" value="approve" class="btn btn-alt">Approve</button>
                    <button type="submit" name="decision" value="hold" class="btn btn-danger">Hold</button>
                </form>
            @elseif($canTechnicianUpdate ?? false)
                <a class="btn btn-alt" href="{{ route('tickets.edit', $ticket) }}">Update Status</a>
            @endif
        </div>
    </div>

    @php
        $statusLabel = match ($ticket->status) {
            'logged' => 'Logged/New',
            'pending_approval' => 'Pending',
            default => str($ticket->status)->replace('_', ' ')->title(),
        };
    @endphp

    <section class="card" style="margin-bottom:1rem;">
        <div class="form-grid" style="margin-bottom:0;">
            <div>
                <div class="muted">Title</div>
                <div>{{ $ticket->title }}</div>
            </div>
            <div>
                <div class="muted">Status</div>
                <span class="status-pill status-{{ $ticket->status }}">{{ $statusLabel }}</span>
            </div>
            <div>
                <div class="muted">Priority</div>
                <div>{{ str($ticket->priority)->title() }}</div>
            </div>
            <div>
                <div class="muted">Property</div>
                <div>{{ $ticket->property->name }}</div>
            </div>
            <div>
                <div class="muted">Category</div>
                <div>{{ $ticket->category->name }}</div>
            </div>
            <div>
                <div class="muted">Unit</div>
                <div>{{ $ticket->unit ?: '-' }}</div>
            </div>
            <div>
                <div class="muted">Reporter</div>
                <div>{{ $ticket->reporter?->name ?? '-' }}</div>
            </div>
            <div>
                <div class="muted">Assigned Technician</div>
                <div>{{ $ticket->technician?->name ?? 'Unassigned' }}</div>
            </div>
            <div>
                <div class="muted">Expected Completion</div>
                <div>{{ $ticket->etd?->format('Y-m-d H:i') ?? '-' }}</div>
            </div>
            <div>
                <div class="muted">Estimated Cost</div>
                <div>{{ $ticket->estimated_cost !== null ? number_format((float) $ticket->estimated_cost, 2) : '-' }}</div>
            </div>
        </div>
    </section>

    <section class="card" style="margin-bottom:1rem;">
        <h3 style="margin-top:0;">Description</h3>
        <p style="margin-bottom:0; white-space:pre-wrap;">{{ $ticket->description }}</p>
    </section>

    <section class="card" style="margin-bottom:1rem;">
        <h3 style="margin-top:0;">Attachments</h3>

        @if($ticket->attachments->isEmpty())
            <p class="muted" style="margin:0;">No attachments added yet.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>File</th>
                        <th>Uploaded By</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($ticket->attachments as $attachment)
                        <tr>
                            <td>{{ str($attachment->attachment_type)->title() }}</td>
                            <td>
                                <a href="{{ route('media.show', ['path' => $attachment->file_path]) }}" target="_blank" rel="noopener">
                                    {{ $attachment->file_name }}
                                </a>
                            </td>
                            <td>{{ $attachment->uploader?->name ?? 'System' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if(($isOperationsManager ?? false) && $ticket->phases->isNotEmpty())
    <section class="card">
        <h3 style="margin-top:0;">Technician Work Progress — {{ $ticket->technician?->name ?? 'Unassigned' }}</h3>
        <div class="table-wrap" style="margin-bottom:1rem;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f5f5f5; border-bottom:2px solid #ddd;">
                        <th style="padding:0.75rem; text-align:left;">Phase</th>
                        <th style="padding:0.75rem; text-align:left;">Status</th>
                        <th style="padding:0.75rem; text-align:left;">Notes</th>
                        <th style="padding:0.75rem; text-align:left;">Files</th>
                        <th style="padding:0.75rem; text-align:left;">Started</th>
                        <th style="padding:0.75rem; text-align:left;">Completed</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($ticket->phases as $phase)
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:0.75rem;"><strong>{{ $phase->phase_name }}</strong></td>
                        <td style="padding:0.75rem;">
                            <span class="status-pill status-{{ $phase->status }}">{{ str($phase->status)->replace('_',' ')->title() }}</span>
                        </td>
                        <td style="padding:0.75rem; max-width:300px; white-space:pre-wrap;">{{ $phase->technician_notes ?: '-' }}</td>
                        <td style="padding:0.75rem;">
                            @if($phase->attachments->isNotEmpty())
                                <div style="display:flex; flex-wrap:wrap; gap:0.4rem;">
                                @foreach($phase->attachments as $att)
                                    <a href="{{ route('media.show', ['path' => $att->file_path]) }}" target="_blank" style="display:inline-flex; align-items:center; gap:0.25rem; font-size:0.82rem; padding:0.2rem 0.5rem; background:#e3f2fd; border-radius:4px; text-decoration:none;">
                                        @if($att->attachment_type === 'image')
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        @endif
                                        {{ $att->file_name }}
                                    </a>
                                @endforeach
                                </div>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                        <td style="padding:0.75rem;"><small>{{ $phase->started_at?->format('M d, Y H:i') ?? '-' }}</small></td>
                        <td style="padding:0.75rem;"><small>{{ $phase->completed_at?->format('M d, Y H:i') ?? '-' }}</small></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif

    @if(($isOperationsManager ?? false) && in_array($ticket->status, ['in_progress', 'assigned', 'logged']))
    <section class="card">
        <h3 style="margin-top:0;">Manage Work Phases</h3>
        <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="card" enctype="multipart/form-data" style="background:#f0f8ff; border:2px solid #2196F3;">
            @csrf
            @method('PUT')
            
            @php
                $currentPhase = $ticket->phases->where('status', 'in_progress')->first();
                if (!$currentPhase) {
                    $lastPhase = $ticket->phases->sortByDesc('phase_number')->first();
                    $nextPhaseNumber = ($lastPhase?->phase_number ?? 0) + 1;
                } else {
                    $nextPhaseNumber = $currentPhase->phase_number;
                }
            @endphp
            
            <h4 style="margin-top:0;">Phase {{ $nextPhaseNumber }} - Add Work Details</h4>

            <div style="margin-bottom:1rem;">
                <label for="technician_notes_ops">Work Notes</label>
                <textarea
                    id="technician_notes_ops"
                    name="technician_notes"
                    placeholder="Describe what was done in this phase..."
                    style="min-height:100px;">{{ old('technician_notes', '') }}</textarea>
            </div>

            <div style="margin-bottom:1rem;">
                <label for="phase_image_ops">Upload Photo</label>
                <input
                    id="phase_image_ops"
                    type="file"
                    name="phase_image"
                    accept="image/*">
                <small class="muted">Upload progress photos or images of completed work</small>
            </div>

            <div style="margin-bottom:1rem;">
                <label for="phase_document_ops">Upload Document</label>
                <input
                    id="phase_document_ops"
                    type="file"
                    name="phase_document"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.txt">
                <small class="muted">Upload reports, invoices, or other supporting documents</small>
            </div>

            <div style="display:flex; gap:0.5rem;">
                <button type="submit" name="action" value="save_phase" class="btn">Save Phase</button>
                <button type="submit" name="action" value="complete_phase" class="btn btn-success">Complete Phase & Next</button>
            </div>
        </form>
    </section>
    @endif

    <script>
        (function () {
            const refreshIntervalMs = 3600000; // 1 hour

            window.setInterval(function () {
                window.location.reload();
            }, refreshIntervalMs);
        })();
    </script>
@endsection