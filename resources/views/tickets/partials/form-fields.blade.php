@php
    $editMode = $editMode ?? false;
@endphp

<div class="form-grid">
    <div>
        <label for="title">Title</label>
        <input id="title" type="text" name="title" value="{{ old('title', $ticket->title ?? '') }}" required>
    </div>

    <div>
        <label for="property_id">Property</label>
        <select id="property_id" name="property_id" required>
            <option value="">Select Property</option>
            @foreach($properties as $property)
                <option value="{{ $property->id }}" @selected((string) old('property_id', $ticket->property_id ?? '') === (string) $property->id)>{{ $property->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="maintenance_category_id">Category</label>
        <select id="maintenance_category_id" name="maintenance_category_id" required>
            <option value="">Select Category</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('maintenance_category_id', $ticket->maintenance_category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="unit">Unit</label>
        <input id="unit" type="text" name="unit" value="{{ old('unit', $ticket->unit ?? '') }}">
    </div>

    @if($editMode)
        <div>
            <label for="assigned_to">Assigned Technician</label>
            <select id="assigned_to" name="assigned_to">
                <option value="">Unassigned</option>
                @foreach($technicians as $technician)
                    <option value="{{ $technician->id }}" @selected((string) old('assigned_to', $ticket->assigned_to ?? '') === (string) $technician->id)>{{ $technician->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="status">Status</label>
            <select id="status" name="status" required>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $ticket->status ?? '') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>
    @else
        <div>
            <label for="reported_by">Reporter</label>
            <select id="reported_by" name="reported_by" required>
                <option value="">Select Reporter</option>
                @foreach($reporters as $reporter)
                    <option value="{{ $reporter->id }}" @selected((string) old('reported_by') === (string) $reporter->id)>{{ $reporter->name }} ({{ $reporter->role }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="assigned_to">Assign Technician (optional)</label>
            <select id="assigned_to" name="assigned_to">
                <option value="">Unassigned</option>
                @foreach($technicians as $technician)
                    <option value="{{ $technician->id }}" @selected((string) old('assigned_to') === (string) $technician->id)>{{ $technician->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div>
        <label for="priority">Priority</label>
        <select id="priority" name="priority" required>
            @foreach($priorities as $priority)
                <option value="{{ $priority }}" @selected(old('priority', $ticket->priority ?? 'medium') === $priority)>{{ str($priority)->title() }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="etd">Expected Completion Date & Time</label>
        <input id="etd" type="datetime-local" name="etd" value="{{ old('etd', isset($ticket->etd) ? $ticket->etd->format('Y-m-d\TH:i') : '') }}">
    </div>
</div>

<div style="margin-bottom: 1rem;">
    <label for="description">Fault Description</label>
    <textarea id="description" name="description" required>{{ old('description', $ticket->description ?? '') }}</textarea>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <h3 style="margin-top: 0;">Fault Attachments</h3>

    <div style="margin-bottom: 0.8rem;">
        <label for="image_attachments">Upload Pictures</label>
        <input id="image_attachments" type="file" name="image_attachments[]" accept="image/*" multiple>
        <p class="muted" style="margin: 0.35rem 0 0; font-size: 0.88rem;">Accepted image formats up to 5MB each.</p>
    </div>

    <div style="margin-bottom: 0.8rem;">
        <label for="camera_attachment">Take a Picture</label>
        <input id="camera_attachment" type="file" name="camera_attachment" accept="image/*" capture="environment">
        <p class="muted" style="margin: 0.35rem 0 0; font-size: 0.88rem;">On supported mobile devices, this opens the camera.</p>
    </div>

    <div style="margin-bottom: 0.4rem;">
        <label for="document_attachments">Upload Documents</label>
        <input id="document_attachments" type="file" name="document_attachments[]" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.csv" multiple>
        <p class="muted" style="margin: 0.35rem 0 0; font-size: 0.88rem;">PDF, DOC, DOCX, XLS, XLSX, TXT, CSV up to 10MB each.</p>
    </div>

    @if($editMode)
        <div style="margin-top: 1rem;">
            <h4 style="margin: 0 0 0.5rem;">Existing Attachments</h4>

            @if(($ticket->attachments ?? collect())->isEmpty())
                <p class="muted" style="margin: 0;">No attachments added yet.</p>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Type</th>
                            <th>File</th>
                            <th>Uploaded By</th>
                            <th>Action</th>
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
                                <td>
                                    <label style="display:flex; align-items:center; gap:0.4rem;">
                                        <input type="checkbox" name="remove_attachment_ids[]" value="{{ $attachment->id }}" style="width:auto;" @checked(collect(old('remove_attachment_ids', []))->contains((string) $attachment->id) || collect(old('remove_attachment_ids', []))->contains($attachment->id))>
                                        <span>Remove</span>
                                    </label>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
