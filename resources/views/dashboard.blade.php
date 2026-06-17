@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
    <h2>Operations Dashboard</h2>
    <p class="muted">Track current maintenance requests across all properties.</p>

    <section class="grid">
        <article class="card">
            <div class="muted">Total Tickets</div>
            <div class="metric">{{ $metrics['total'] }}</div>
        </article>
        <article class="card">
            <div class="muted">New</div>
            <div class="metric">{{ $metrics['new'] }}</div>
        </article>
        <article class="card">
            <div class="muted">In Progress</div>
            <div class="metric">{{ $metrics['in_progress'] }}</div>
        </article>
        <article class="card">
            <div class="muted">Overdue</div>
            <div class="metric">{{ $metrics['overdue'] }}</div>
        </article>
        <article class="card">
            <div class="muted">Completed</div>
            <div class="metric">{{ $metrics['completed'] }}</div>
        </article>
        <article class="card">
            <div class="muted">Closed</div>
            <div class="metric">{{ $metrics['closed'] }}</div>
        </article>
    </section>

    <section class="card" style="margin-top: 1rem;">
        <h3>Technician Workload</h3>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Technician</th>
                    <th>Assigned Tickets</th>
                </tr>
                </thead>
                <tbody>
                @forelse($workload as $entry)
                    <tr>
                        <td>{{ $entry->name }}</td>
                        <td>{{ $entry->tickets_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No assignments yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card" style="margin-top: 1rem;">
        <h3>Property Statistics</h3>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Property</th>
                    <th>Total Tickets</th>
                </tr>
                </thead>
                <tbody>
                @forelse($propertyStats as $entry)
                    <tr>
                        <td>{{ $entry->name }}</td>
                        <td>{{ $entry->tickets_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No properties found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
