@extends('layouts.app', ['title' => 'Users'])

@section('content')
    <h2>Users</h2>
    <div style="display:flex; justify-content:flex-end; margin-bottom:0.8rem;">
        <a class="btn" href="{{ route('admin.users.create') }}">Add User</a>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
            <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Approval</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $u)
                <tr>
                    <td>
                        @if($u->profile_photo_path)
                            <img src="{{ route('media.show', ['path' => $u->profile_photo_path]) }}" alt="{{ $u->name }} profile photo" class="user-photo-thumb">
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->role === \App\Models\User::ROLE_ADMIN ? 'Super Admin' : str($u->role)->replace('_', ' ')->title() }}</td>
                    <td>
                        @if($u->is_approved)
                            <span class="muted">Approved</span>
                        @else
                            <span style="color:#b45309; font-weight:600;">Pending</span>
                        @endif
                    </td>
                    <td>{{ $u->phone ?: '-' }}</td>
                    <td>
                        <div class="row-actions">
                            @if(! $u->is_approved)
                                <form method="POST" action="{{ route('admin.users.approve', $u) }}" data-loader-action="user-approve">
                                    @csrf
                                    <button type="submit" class="btn" data-loader-action="user-approve">Approve</button>
                                </form>
                            @endif
                            <a class="btn btn-alt" href="{{ route('admin.users.edit', $u) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('Delete user?')" data-loader-action="user-delete">
                                @csrf
                                @method('DELETE')
                                <button type="submit" data-loader-action="user-delete">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">{{ $users->links() }}</div>

    <section class="card table-wrap" style="margin-top: 1rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:0.75rem;">
            <h3 style="margin:0;">Recent Audit Trail</h3>
            <div class="muted" style="display:flex; gap:0.6rem; font-size:0.9rem;">
                <a href="{{ route('admin.users.index') }}" @if($auditAction === '') style="font-weight:700;" @endif>All</a>
                <a href="{{ route('admin.users.index', ['audit_action' => 'created']) }}" @if($auditAction === 'created') style="font-weight:700;" @endif>Created</a>
                <a href="{{ route('admin.users.index', ['audit_action' => 'updated']) }}" @if($auditAction === 'updated') style="font-weight:700;" @endif>Updated</a>
                <a href="{{ route('admin.users.index', ['audit_action' => 'deleted']) }}" @if($auditAction === 'deleted') style="font-weight:700;" @endif>Deleted</a>
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
                    <td>{{ $audit->meta['name'] ?? $audit->meta['email'] ?? ('#'.$audit->auditable_id) }}</td>
                    <td>{{ $audit->actor?->name ?? 'System' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No audit activity yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
