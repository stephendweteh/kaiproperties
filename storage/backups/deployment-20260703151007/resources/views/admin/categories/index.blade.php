@extends('layouts.app', ['title' => 'Categories'])

@section('content')
    <h2>Maintenance Categories</h2>
    <div style="display:flex; justify-content:flex-end; margin-bottom:0.8rem;">
        <a class="btn" href="{{ route('admin.categories.create') }}">Add Category</a>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->description ?: '-' }}</td>
                    <td>{{ $category->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>
                        <div class="row-actions">
                            <a class="btn btn-alt" href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete category?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No categories found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">{{ $categories->links() }}</div>

    <section class="card table-wrap" style="margin-top: 1rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:0.75rem;">
            <h3 style="margin:0;">Recent Audit Trail</h3>
            <div class="muted" style="display:flex; gap:0.6rem; font-size:0.9rem;">
                <a href="{{ route('admin.categories.index') }}" @if($auditAction === '') style="font-weight:700;" @endif>All</a>
                <a href="{{ route('admin.categories.index', ['audit_action' => 'created']) }}" @if($auditAction === 'created') style="font-weight:700;" @endif>Created</a>
                <a href="{{ route('admin.categories.index', ['audit_action' => 'updated']) }}" @if($auditAction === 'updated') style="font-weight:700;" @endif>Updated</a>
                <a href="{{ route('admin.categories.index', ['audit_action' => 'deleted']) }}" @if($auditAction === 'deleted') style="font-weight:700;" @endif>Deleted</a>
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
