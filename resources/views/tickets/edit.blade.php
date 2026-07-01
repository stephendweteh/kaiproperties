@extends('layouts.app', ['title' => ($reviewMode ?? false) ? 'Approve Ticket' : (($technicianMode ?? false) ? 'Ticket' : 'Edit Ticket')])

@section('content')
    <h2>{{ ($reviewMode ?? false) ? 'Approve Ticket' : (($technicianMode ?? false) ? 'Ticket' : 'Edit Ticket') }} {{ $ticket->ticket_no }}</h2>

    <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="card" enctype="multipart/form-data">
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
                    <input type="text" value="{{ $ticket->estimated_cost !== null ? number_format((float) $ticket->estimated_cost, 2) : '-' }}" disabled>
                </div>
                <div>
                    <label for="assigned_to">Assigned Technician</label>
                    <select id="assigned_to" name="assigned_to" required>
                        <option value="">Select Technician</option>
                        @foreach($technicians as $technician)
                            <option value="{{ $technician->id }}" @selected((string) old('assigned_to', $ticket->assigned_to ?? '') === (string) $technician->id)>{{ $technician->name }}</option>
                        @endforeach
                    </select>
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
                <button type="submit" class="btn btn-success">Executed</button>
            @else
                {{-- Technician phase buttons are already inside the technician-form partial --}}
            @endif
        @else
            <button type="submit">{{ ($reviewMode ?? false) ? 'Submit Decision' : 'Update Status' }}</button>
        @endif
    </form>
@endsection
