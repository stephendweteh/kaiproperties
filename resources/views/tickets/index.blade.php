@extends('layouts.app', ['title' => 'Tickets'])

@section('content')
    <h2>Maintenance Tickets</h2>

    @php
        $statusLabels = [
            'logged' => 'Logged/New',
            'pending_approval' => 'Pending',
        ];
    @endphp

    <form method="GET" action="{{ route('tickets.index') }}" class="card" style="margin-bottom: 1rem;">
        <div class="filters">
            <input type="text" name="search" placeholder="Search by ticket no/title/description" value="{{ request('search') }}">

            <select name="status">
                <option value="">All Statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $statusLabels[$status] ?? str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>

            <select name="property_id">
                <option value="">All Properties</option>
                @foreach($properties as $property)
                    <option value="{{ $property->id }}" @selected((string) request('property_id') === (string) $property->id)>{{ $property->name }}</option>
                @endforeach
            </select>

            <select name="maintenance_category_id">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('maintenance_category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>

            @if($technicians->isNotEmpty())
                <select name="assigned_to">
                    <option value="">All Technicians</option>
                    @foreach($technicians as $technician)
                        <option value="{{ $technician->id }}" @selected((string) request('assigned_to') === (string) $technician->id)>{{ $technician->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <button type="submit">Apply Filters</button>
    </form>

    @if($canCreateTickets)
        <div style="display: flex; justify-content: flex-end; margin-bottom: 0.7rem;">
            <a class="btn" href="{{ route('tickets.create') }}">Create Ticket</a>
        </div>
    @endif

    <section class="card" style="margin-bottom: 0.9rem;">
        <h3>Status Colors</h3>
        <div class="status-legend">
            <span class="status-pill status-logged">Logged/New</span>
            <span class="status-pill status-in_progress">In Progress</span>
            <span class="status-pill status-overdue">Overdue</span>
            <span class="status-pill status-completed">Completed</span>
            <span class="status-pill status-closed">Closed</span>
        </div>
    </section>

    <div class="card table-wrap">
        <table>
            <thead>
            <tr>
                <th>Ticket No</th>
                <th>Title</th>
                <th>Property</th>
                <th>Category</th>
                <th>Status</th>
                <th>Technician</th>
                <th>ETD</th>
                <th>Estimated Cost</th>
                <th>Attachments</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($tickets as $ticket)
                <tr>
                    <td>{{ $ticket->ticket_no }}</td>
                    <td>{{ $ticket->title }}</td>
                    <td>{{ $ticket->property->name }}</td>
                    <td>{{ $ticket->category->name }}</td>
                    <td>
                        <a href="{{ route('tickets.show', $ticket) }}" style="text-decoration:none;">
                            <span class="status-pill status-{{ $ticket->status }}" data-ticket-status="{{ $ticket->id }}">{{ $statusLabels[$ticket->status] ?? str($ticket->status)->replace('_', ' ') }}</span>
                        </a>
                    </td>
                    <td>{{ $ticket->technician?->name ?? 'Unassigned' }}</td>
                    <td>{{ $ticket->etd?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>{{ $ticket->estimated_cost !== null ? number_format((float) $ticket->estimated_cost, 2) : '-' }}</td>
                    <td>
                        @php
                            $imageAttachments = $ticket->attachments->where('attachment_type', 'image')->take(3);
                            $documentAttachments = $ticket->attachments->where('attachment_type', 'document')->take(3);
                            $hiddenImages = $ticket->attachments->where('attachment_type', 'image')->count() - $imageAttachments->count();
                            $hiddenDocs = $ticket->attachments->where('attachment_type', 'document')->count() - $documentAttachments->count();
                        @endphp

                        @if($ticket->attachments->isEmpty())
                            <span class="muted">-</span>
                        @else
                            @if($imageAttachments->isNotEmpty())
                                <div class="ticket-attachment-thumbs">
                                    @foreach($imageAttachments as $attachment)
                                        <a href="{{ route('media.show', ['path' => $attachment->file_path]) }}" target="_blank" rel="noopener" title="{{ $attachment->file_name }}">
                                            <img src="{{ route('media.show', ['path' => $attachment->file_path]) }}" alt="{{ $attachment->file_name }}" loading="lazy">
                                        </a>
                                    @endforeach
                                    @if($hiddenImages > 0)
                                        <span class="muted" style="font-size: 0.75rem;">+{{ $hiddenImages }} more</span>
                                    @endif
                                </div>
                            @endif

                            @if($documentAttachments->isNotEmpty())
                                <div class="ticket-attachment-docs">
                                    @foreach($documentAttachments as $attachment)
                                        <a href="{{ route('media.show', ['path' => $attachment->file_path]) }}" target="_blank" rel="noopener" download>
                                            {{ $attachment->file_name }}
                                        </a>
                                    @endforeach
                                    @if($hiddenDocs > 0)
                                        <span class="muted" style="font-size: 0.75rem;">+{{ $hiddenDocs }} more</span>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:0.3rem; align-items:center; flex-wrap:nowrap;">
                        @if($canEditTickets)
                            <a class="btn btn-alt btn-icon" href="{{ route('tickets.edit', $ticket) }}" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" onsubmit="return confirm('Delete this ticket permanently?');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-icon" title="Delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                            </form>
                        @elseif($isTechnician ?? false)
                            @if(in_array($ticket->status, ['logged', 'assigned', 'in_progress'], true))
                                <a class="btn btn-alt btn-icon" href="{{ route('tickets.edit', $ticket) }}" title="View Task">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <span>View Task</span>
                                </a>
                            @else
                                <button type="button" class="btn btn-alt btn-icon" disabled title="Status update disabled while ticket is on hold.">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <span>View Task</span>
                                </button>
                            @endif
                        @elseif($isOperationsManager ?? false)
                            <a class="btn btn-alt btn-icon" href="{{ route('tickets.show', $ticket) }}" title="View Phases">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <span>View</span>
                            </a>
                        @else
                            <a class="btn btn-alt btn-icon" href="{{ route('tickets.show', $ticket) }}" title="View">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <span>View</span>
                            </a>
                        @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">No tickets found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">{{ $tickets->links() }}</div>

@endsection
