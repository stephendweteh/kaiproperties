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

    <section class="card">
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
                                <a href="{{ asset('storage/'.$attachment->file_path) }}" target="_blank" rel="noopener">
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
@endsection