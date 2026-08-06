@extends('layouts.app', ['title' => ($reviewMode ?? false) ? 'Approve Ticket' : (($technicianMode ?? false) ? 'Ticket' : 'Edit Ticket')])

@section('content')
    @once
        <style>
            .assign-tech-list {
                max-height: 180px;
                overflow-y: auto;
                border: 1px solid #d5dde6;
                border-radius: 8px;
                padding: 0.55rem 0.7rem;
                background: #fff;
            }

            .assign-tech-option {
                display: flex;
                align-items: flex-start;
                gap: 0.5rem;
                margin-bottom: 0.45rem;
                font-weight: 500;
                line-height: 1.25;
                cursor: pointer;
            }

            .assign-tech-option:last-child {
                margin-bottom: 0;
            }

            .assign-tech-option input[type='checkbox'] {
                width: 16px;
                height: 16px;
                margin: 0.08rem 0 0;
                flex: 0 0 auto;
            }

            .assign-tech-option span {
                display: inline-block;
                padding-top: 0.01rem;
            }
        </style>
    @endonce

    <h2>{{ ($reviewMode ?? false) ? 'Approve Ticket' : (($technicianMode ?? false) ? 'Ticket' : 'Edit Ticket') }} {{ $ticket->ticket_no }}</h2>

    <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="card" enctype="multipart/form-data" data-loader-action="{{ ($reviewMode ?? false) ? 'ticket-review' : (($technicianMode ?? false) ? 'ticket-phase' : 'ticket-update') }}">
        @csrf
        @method('PUT')

        @if($reviewMode ?? false)
            <div class="form-grid">
                <div>
                    <label>Title</label>
                    <input type="text" value="{{ $ticket->title }}" disabled>
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
                    <label>Current Status</label>
                    <input type="text" value="{{ $ticket->status === 'pending_approval' ? 'Pending' : str($ticket->status)->replace('_', ' ')->title() }}" disabled>
                </div>
                <div>
                    <label for="status">Decision</label>
                    <select id="status" name="status" required>
                        <option value="logged" @selected(old('status') === 'logged')>Approve (Set to Logged/New)</option>
                        <option value="on_hold" @selected(old('status') === 'on_hold')>Place On Hold</option>
                    </select>
                </div>
                <div>
                    <label>Reporter</label>
                    <input type="text" value="{{ $ticket->reporter?->name ?? '-' }}" disabled>
                </div>
                <div>
                    <label>Estimated Cost</label>
                    @php
                        $symbol = $ticket->estimated_cost_currency
                            ? (\App\Models\Ticket::ESTIMATED_COST_CURRENCY_SYMBOLS[$ticket->estimated_cost_currency] ?? '')
                            : '';
                    @endphp
                    <input type="text" value="{{ $ticket->estimated_cost !== null ? ($symbol.number_format((float) $ticket->estimated_cost, 2)) : '-' }}" disabled>
                </div>
                <div>
                    <label>Assigned Technicians</label>
                    @php
                        $selectedTechnicianIds = collect(old('assigned_to', $ticket->technicians->pluck('id')->all()))
                            ->filter()
                            ->map(fn ($id) => (string) $id)
                            ->all();
                    @endphp
                    <div class="assign-tech-list">
                        @forelse($technicians as $technician)
                            <label class="assign-tech-option">
                                <input
                                    type="checkbox"
                                    name="assigned_to[]"
                                    value="{{ $technician->id }}"
                                    @checked(in_array((string) $technician->id, $selectedTechnicianIds, true))>
                                <span>{{ $technician->name }}</span>
                            </label>
                        @empty
                            <small class="muted">No technicians available.</small>
                        @endforelse
                    </div>
                    <small class="muted">Select one or more technicians.</small>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label>Description</label>
                <textarea disabled>{{ $ticket->description }}</textarea>
            </div>
        @elseif($technicianMode ?? false)
            @include('tickets.partials.technician-form')
        @else
            @include('tickets.partials.form-fields', ['editMode' => true])
        @endif

        @if($technicianMode ?? false)
            @if($isOperationsManager ?? false)
                <input type="hidden" name="action" value="mark_completed">
                <button type="submit" class="btn btn-success" data-loader-action="ticket-mark-completed">Executed</button>
            @else
                {{-- Technician phase buttons are already inside the technician-form partial --}}
            @endif
        @else
            <button type="submit" data-loader-action="{{ ($reviewMode ?? false) ? 'ticket-review' : 'ticket-update' }}">{{ ($reviewMode ?? false) ? 'Submit Decision' : 'Update Status' }}</button>
        @endif
    </form>
@endsection
