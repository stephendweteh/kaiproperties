@if($technicianMode ?? false)
    <div class="form-grid">
        <div>
            <label>Title</label>
            <input type="text" value="{{ $ticket->title }}" disabled>
        </div>
        <div>
            <label>Current Status</label>
            <input type="text" value="{{ $ticket->status === 'logged' ? 'Logged/New' : str($ticket->status)->replace('_', ' ')->title() }}" disabled>
        </div>
        <div>
            <label>Property</label>
            <input type="text" value="{{ $ticket->property->name }}" disabled>
        </div>
        <div>
            <label>Category</label>
            <input type="text" value="{{ $ticket->category->name }}" disabled>
        </div>
        <div>
            <label>Assigned Technician</label>
            <input type="text" value="{{ $ticket->technician?->name ?? '-' }}" disabled>
        </div>
    </div>

    <div style="margin-bottom: 1rem;">
        <label>Description</label>
        <textarea disabled>{{ $ticket->description }}</textarea>
    </div>

    <!-- Phase Section -->
    <h3 style="margin-top: 2rem; margin-bottom: 1rem;">Work Progress</h3>

    <!-- Completed/Saved Phases List View -->
    @if(!$ticket->phases->isEmpty())
        <div style="margin-bottom: 2rem;">
            <div class="table-wrap">
                <table style="width: 100%; min-width: 1280px; border-collapse: collapse; table-layout: auto;">
                    <thead>
                        <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                            <th style="padding: 0.75rem; text-align: left;">Phase</th>
                            <th style="padding: 0.75rem; text-align: left;">Status</th>
                            <th style="padding: 0.75rem; text-align: left;">Notes</th>
                            <th style="padding: 0.75rem; text-align: left;">Manager Comment</th>
                            <th style="padding: 0.75rem; text-align: left;">Files</th>
                            <th style="padding: 0.75rem; text-align: left;">Started</th>
                            <th style="padding: 0.75rem; text-align: left;">Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ticket->phases as $phase)
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 0.75rem;"><strong>{{ $phase->phase_name }}</strong></td>
                                <td style="padding: 0.75rem;">
                                    <span class="status-pill status-{{ $phase->status }}">{{ str($phase->status)->replace('_', ' ')->title() }}</span>
                                </td>
                                <td style="padding: 0.75rem; max-width: 360px; white-space: pre-wrap; word-break: break-word; text-align: left; vertical-align: top;">
                                    @if($phase->technician_notes)
                                        <small style="display:block; text-align:left; white-space:pre-wrap;">{{ $phase->technician_notes }}</small>
                                    @else
                                        <small class="muted">-</small>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem; max-width: 380px; white-space: pre-wrap; word-break: break-word; text-align: left; vertical-align: top;">
                                    @if($phase->manager_notes)
                                        <small style="display:block; text-align:left; white-space:pre-wrap;">{{ $phase->manager_notes }}</small>
                                    @else
                                        <small class="muted">-</small>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem;">
                                    @if($phase->attachments->count() > 0)
                                        <small>{{ $phase->attachments->count() }} file(s)</small>
                                        <div style="margin-top: 0.3rem; display: flex; flex-wrap: wrap; gap: 0.3rem;">
                                            @foreach($phase->attachments as $attachment)
                                                <a href="{{ route('media.show', ['path' => $attachment->file_path]) }}" target="_blank" style="font-size: 0.8rem; padding: 0.2rem 0.5rem; background: #e3f2fd; border-radius: 3px; text-decoration: none;">
                                                    {{ $attachment->attachment_type === 'image' ? '📷' : '📄' }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <small class="muted">-</small>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem;">
                                    <small>{{ $phase->started_at?->format('M d') ?? '-' }}</small>
                                </td>
                                <td style="padding: 0.75rem;">
                                    <small>{{ $phase->completed_at?->format('M d') ?? '-' }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 1rem; text-align: center; color: #999;">No phases yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Current/Next Phase Form -->
    @php
        // Get the current in_progress phase
        $currentPhase = $ticket->phases->where('status', 'in_progress')->first();
        
        // If no in_progress phase, create the next phase number
        if (!$currentPhase) {
            $lastPhase = $ticket->phases->sortByDesc('phase_number')->first();
            $nextPhaseNumber = ($lastPhase?->phase_number ?? 0) + 1;
        } else {
            $nextPhaseNumber = $currentPhase->phase_number;
        }
    @endphp

    <div class="card" style="padding: 1.5rem; background: #f0f8ff; border: 2px solid #2196F3;">
        <h4 style="margin-top: 0;">Phase {{ $nextPhaseNumber }} - Enter Details & Click Execute</h4>

        <div style="margin-bottom: 1rem;">
            <label for="technician_notes">Work Notes</label>
            <textarea
                id="technician_notes"
                name="technician_notes"
                placeholder="Describe what you did in this phase..."
                style="min-height: 100px;">{{ old('technician_notes', $currentPhase?->technician_notes ?? '') }}</textarea>
        </div>

        <div style="margin-bottom: 1rem;">
            <label for="phase_image">Take Photo / Upload Image</label>
            <input
                id="phase_image"
                type="file"
                name="phase_image"
                accept="image/*"
                capture="camera">
            <small class="muted">Upload progress photos or images of completed work</small>
        </div>

        <div style="margin-bottom: 1rem;">
            <label for="phase_document">Upload Document</label>
            <input
                id="phase_document"
                type="file"
                name="phase_document"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.txt">
            <small class="muted">Upload reports, invoices, or other supporting documents</small>
        </div>

        @if(!($isOperationsManager ?? false))
            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" name="action" value="save_phase" class="btn" data-loader-action="ticket-phase-save">Save Phase</button>
                <button type="submit" name="action" value="complete_phase" class="btn btn-success" data-loader-action="ticket-phase-complete">Complete Phase & Next</button>
            </div>
        @endif
    </div>
@endif
