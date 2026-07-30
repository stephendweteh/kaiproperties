@php
    $editMode = $editMode ?? false;
    $technicianMode = $technicianMode ?? false;
@endphp

<div class="form-grid">
    <div style="grid-column: 1 / -1;">
        <label for="title">Title</label>
        <input id="title" type="text" name="title" value="{{ old('title', $ticket->title ?? '') }}" required>
    </div>

    @if(!$editMode && isset($customers))
    <div>
        <label for="customer_id">Customer</label>
        <select id="customer_id" name="customer_id">
            <option value="">All Customers</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" data-properties="{{ implode(',', $customer->properties->pluck('id')->toArray()) }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->name }}</option>
            @endforeach
        </select>
    </div>
    @endif

    <div>
        <label for="property_id">Property</label>
        <select id="property_id" name="property_id" required>
            <option value="">Select Property</option>
            @foreach($properties as $property)
                <option value="{{ $property->id }}" data-customer-id="{{ $property->customer_id ?? '' }}" @selected((string) old('property_id', $ticket->property_id ?? '') === (string) $property->id)>{{ $property->name }}</option>
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
        @if(! $technicianMode)
            <div>
                <label for="assigned_to">Assigned Technician</label>
                <select id="assigned_to" name="assigned_to">
                    <option value="">Unassigned</option>
                    @foreach($technicians as $technician)
                        <option value="{{ $technician->id }}" @selected((string) old('assigned_to', $ticket->assigned_to ?? '') === (string) $technician->id)>{{ $technician->name }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <input type="hidden" name="assigned_to" value="{{ $ticket->assigned_to }}">
        @endif

        <div>
            <label for="status">Status</label>
            <select id="status" name="status" required>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $ticket->status ?? '') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>
    @else
        @if($isTenant)
            <input type="hidden" name="reported_by" value="{{ auth()->id() }}">
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

    <div>
        <label for="estimated_cost">Estimated Cost</label>
        <div style="display:flex; align-items:center; gap:0.45rem;">
            <select id="estimated_cost_currency" name="estimated_cost_currency" style="max-width:110px;">
                @php
                    $selectedCurrency = old('estimated_cost_currency', $ticket->estimated_cost_currency ?? 'GHS');
                    $currencyLabels = \App\Models\Ticket::ESTIMATED_COST_CURRENCY_SYMBOLS;
                @endphp
                @foreach(($estimatedCostCurrencies ?? ['GBP', 'USD', 'EUR', 'GHS', 'CNY']) as $currency)
                    <option value="{{ $currency }}" @selected($selectedCurrency === $currency)>{{ $currencyLabels[$currency] ?? $currency }}</option>
                @endforeach
            </select>
            <input id="estimated_cost" type="number" name="estimated_cost" min="0" step="0.01" value="{{ old('estimated_cost', $ticket->estimated_cost ?? '') }}" placeholder="0.00">
        </div>
    </div>
</div>

<div style="margin-bottom: 1rem;">
    <label for="description">Description</label>
    <textarea id="description" name="description" required>{{ old('description', $ticket->description ?? '') }}</textarea>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <h3 style="margin-top: 0;">Attachments</h3>

    <style>
        .attachment-dropzone {
            border: 2px dashed #c8d3df;
            border-radius: 10px;
            padding: 0.85rem;
            background: #f9fbfd;
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }

        .attachment-dropzone.is-dragover {
            border-color: #1f7ae0;
            background: #eef5ff;
        }

        .attachment-preview-list {
            margin-top: 0.55rem;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 0.5rem;
        }

        .attachment-preview-item {
            position: relative;
            border: 1px solid #d6dee8;
            border-radius: 8px;
            background: #fff;
            padding: 0.35rem;
            overflow: hidden;
        }

        .attachment-preview-item img {
            width: 100%;
            height: 78px;
            object-fit: cover;
            border-radius: 6px;
            display: block;
            background: #f0f4f8;
        }

        .attachment-preview-name {
            display: block;
            margin-top: 0.35rem;
            font-size: 0.72rem;
            color: #42566a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .attachment-preview-remove {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: none;
            background: #d93025;
            color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
            z-index: 2;
        }

        .attachment-preview-remove:hover {
            background: #b42318;
        }

        .attachment-file-list {
            margin-top: 0.55rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .attachment-file-chip {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            max-width: 260px;
            border: 1px solid #d6dee8;
            border-radius: 999px;
            padding: 0.28rem 0.7rem 0.28rem 0.5rem;
            background: #fff;
            font-size: 0.78rem;
            color: #33485d;
        }

        .attachment-file-chip .attachment-preview-name {
            margin: 0;
            max-width: 190px;
            font-size: 0.78rem;
        }

        .attachment-file-remove {
            border: none;
            background: #d93025;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            line-height: 1;
            padding: 0 0.35rem;
            border-radius: 999px;
        }

        .attachment-file-remove:hover {
            background: #b42318;
        }
    </style>

    <div style="margin-bottom: 0.8rem;">
        <label for="image_attachments">Upload Pictures</label>
        <div class="attachment-dropzone" data-dropzone="image_attachments" tabindex="0" role="button" aria-label="Upload pictures by clicking or dragging files here">
            <p class="muted" style="margin: 0 0 0.5rem; font-size: 0.88rem;">Drag and drop images here, or click to browse.</p>
            <input id="image_attachments" type="file" name="image_attachments[]" accept="image/*" multiple>
            <p class="muted" data-file-summary="image_attachments" style="margin: 0.45rem 0 0; font-size: 0.82rem;">No files selected.</p>
            <div class="attachment-preview-list" data-preview="image_attachments"></div>
        </div>
        <p class="muted" style="margin: 0.35rem 0 0; font-size: 0.88rem;">Accepted image formats up to 5MB each.</p>
    </div>

    <div style="margin-bottom: 0.8rem;">
        <label for="camera_attachment">Take a Picture</label>
        <input id="camera_attachment" type="file" name="camera_attachment" accept="image/*" capture="environment">
        <p class="muted" style="margin: 0.35rem 0 0; font-size: 0.88rem;">On supported mobile devices, this opens the camera.</p>
    </div>

    <div style="margin-bottom: 0.4rem;">
        <label for="document_attachments">Upload Documents</label>
        <div class="attachment-dropzone" data-dropzone="document_attachments" tabindex="0" role="button" aria-label="Upload documents by clicking or dragging files here">
            <p class="muted" style="margin: 0 0 0.5rem; font-size: 0.88rem;">Drag and drop documents here, or click to browse.</p>
            <input id="document_attachments" type="file" name="document_attachments[]" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.csv" multiple>
            <p class="muted" data-file-summary="document_attachments" style="margin: 0.45rem 0 0; font-size: 0.82rem;">No files selected.</p>
            <div class="attachment-file-list" data-preview="document_attachments"></div>
        </div>
        <p class="muted" style="margin: 0.35rem 0 0; font-size: 0.88rem;">PDF, DOC, DOCX, XLS, XLSX, TXT, CSV up to 10MB each.</p>
    </div>

    <script>
        (function () {
            const setupDropzone = function (inputId, label) {
                const input = document.getElementById(inputId);
                const zone = document.querySelector('[data-dropzone="' + inputId + '"]');
                const summary = document.querySelector('[data-file-summary="' + inputId + '"]');
                const preview = document.querySelector('[data-preview="' + inputId + '"]');

                if (!input || !zone || !summary || !preview) {
                    return;
                }

                const updateSummary = function () {
                    const count = input.files ? input.files.length : 0;
                    summary.textContent = count > 0
                        ? count + ' ' + label + (count > 1 ? 's selected.' : ' selected.')
                        : 'No files selected.';
                };

                const setFiles = function (files) {
                    const transfer = new DataTransfer();
                    Array.from(files).forEach(function (file) {
                        transfer.items.add(file);
                    });
                    input.files = transfer.files;
                };

                const removeFileAt = function (index) {
                    if (!input.files || index < 0 || index >= input.files.length) {
                        return;
                    }

                    const remaining = Array.from(input.files).filter(function (_file, fileIndex) {
                        return fileIndex !== index;
                    });

                    setFiles(remaining);
                    updateSummary();
                    renderPreview();
                };

                const renderPreview = function () {
                    preview.innerHTML = '';

                    if (!input.files || input.files.length === 0) {
                        return;
                    }

                    Array.from(input.files).forEach(function (file, fileIndex) {
                        if (inputId === 'image_attachments') {
                            const item = document.createElement('div');
                            item.className = 'attachment-preview-item';

                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'attachment-preview-remove';
                            removeBtn.setAttribute('aria-label', 'Remove file');
                            removeBtn.textContent = 'x';
                            removeBtn.addEventListener('click', function (event) {
                                event.preventDefault();
                                event.stopPropagation();
                                removeFileAt(fileIndex);
                            });

                            const image = document.createElement('img');
                            const imageUrl = URL.createObjectURL(file);
                            image.src = imageUrl;
                            image.alt = file.name;
                            image.addEventListener('load', function () {
                                URL.revokeObjectURL(imageUrl);
                            });

                            const name = document.createElement('span');
                            name.className = 'attachment-preview-name';
                            name.textContent = file.name;

                            item.appendChild(removeBtn);
                            item.appendChild(image);
                            item.appendChild(name);
                            preview.appendChild(item);
                            return;
                        }

                        const chip = document.createElement('div');
                        chip.className = 'attachment-file-chip';

                        const icon = document.createElement('span');
                        icon.textContent = 'FILE';

                        const name = document.createElement('span');
                        name.className = 'attachment-preview-name';
                        name.textContent = file.name;

                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'attachment-file-remove';
                        removeBtn.setAttribute('aria-label', 'Remove file');
                        removeBtn.textContent = 'x';
                        removeBtn.addEventListener('click', function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            removeFileAt(fileIndex);
                        });

                        chip.appendChild(icon);
                        chip.appendChild(name);
                        chip.appendChild(removeBtn);
                        preview.appendChild(chip);
                    });
                };

                const mergeFiles = function (incomingFiles) {
                    const transfer = new DataTransfer();

                    if (input.files) {
                        Array.from(input.files).forEach(function (file) {
                            transfer.items.add(file);
                        });
                    }

                    Array.from(incomingFiles).forEach(function (file) {
                        transfer.items.add(file);
                    });

                    input.files = transfer.files;
                    updateSummary();
                    renderPreview();
                };

                const preventDefaults = function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                };

                ['dragenter', 'dragover'].forEach(function (name) {
                    zone.addEventListener(name, function (event) {
                        preventDefaults(event);
                        zone.classList.add('is-dragover');
                    });
                });

                ['dragleave', 'drop'].forEach(function (name) {
                    zone.addEventListener(name, function (event) {
                        preventDefaults(event);
                        zone.classList.remove('is-dragover');
                    });
                });

                zone.addEventListener('drop', function (event) {
                    const files = event.dataTransfer ? event.dataTransfer.files : null;
                    if (!files || files.length === 0) {
                        return;
                    }

                    mergeFiles(files);
                });

                zone.addEventListener('click', function (event) {
                    if (event.target && event.target.closest('.attachment-preview-remove, .attachment-file-remove')) {
                        return;
                    }

                    if (event.target && event.target.tagName === 'INPUT') {
                        return;
                    }

                    input.click();
                });

                zone.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        input.click();
                    }
                });

                input.addEventListener('change', function () {
                    updateSummary();
                    renderPreview();
                });
                updateSummary();
                renderPreview();
            };

            setupDropzone('image_attachments', 'image file');
            setupDropzone('document_attachments', 'document file');
        })();
    </script>

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
                                    <a href="{{ route('media.show', ['path' => $attachment->file_path]) }}" target="_blank" rel="noopener">
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
