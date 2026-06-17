@extends('layouts.app', ['title' => 'Properties'])

@section('content')
    <h2>Properties</h2>
    <div style="display:flex; justify-content:flex-end; margin-bottom:0.8rem;">
        <a class="btn" href="{{ route('admin.properties.create') }}">Add Property</a>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Location</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($properties as $property)
                <tr>
                    <td>{{ $property->name }}</td>
                    <td>{{ $property->code ?: '-' }}</td>
                    <td>{{ trim(($property->city ?: '').' '.($property->state ?: '')) ?: '-' }}</td>
                    <td>{{ $property->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>
                        <div class="row-actions">
                            <a class="btn btn-alt" href="{{ route('admin.properties.edit', $property) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.properties.destroy', $property) }}" onsubmit="return confirm('Delete property?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No properties found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">{{ $properties->links() }}</div>

    <section class="card table-wrap" style="margin-top: 1rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:0.75rem;">
            <h3 style="margin:0;">Recent Audit Trail</h3>
            <div class="muted" style="display:flex; gap:0.6rem; font-size:0.9rem;">
                <a href="{{ route('admin.properties.index') }}" @if($auditAction === '') style="font-weight:700;" @endif>All</a>
                <a href="{{ route('admin.properties.index', ['audit_action' => 'created']) }}" @if($auditAction === 'created') style="font-weight:700;" @endif>Created</a>
                <a href="{{ route('admin.properties.index', ['audit_action' => 'updated']) }}" @if($auditAction === 'updated') style="font-weight:700;" @endif>Updated</a>
                <a href="{{ route('admin.properties.index', ['audit_action' => 'deleted']) }}" @if($auditAction === 'deleted') style="font-weight:700;" @endif>Deleted</a>
            </div>
        </div>
        <table>
            <thead>
            <tr>
                <th>When</th>
                <th>Action</th>
                <th>Record</th>
                <th>By</th>
            </tr>
            </thead>
            <tbody>
            @forelse($recentAudits as $audit)
                <tr>
                    <td>{{ $audit->created_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ str($audit->action)->title() }}</td>
                    <td>{{ $audit->meta['name'] ?? ('#'.$audit->auditable_id) }}</td>
                    <td>{{ $audit->actor?->name ?? 'System' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No audit activity yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
