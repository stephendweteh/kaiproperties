@extends('layouts.app', ['title' => 'Customers'])

@section('content')
    <h2>Customers</h2>
    <div style="display:flex; justify-content:flex-end; margin-bottom:0.8rem;">
        <a class="btn" href="{{ route('admin.customers.create') }}">Add Customer</a>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Properties</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->email ?: '-' }}</td>
                    <td>{{ $customer->phone ?: '-' }}</td>
                    <td>{{ $customer->properties_count }}</td>
                    <td>{{ $customer->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>
                        <div class="row-actions">
                            <a class="btn btn-alt" href="{{ route('admin.customers.show', $customer) }}">View</a>
                            <a class="btn btn-alt" href="{{ route('admin.customers.edit', $customer) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Delete customer?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">No customers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">{{ $customers->links() }}</div>

    <section class="card table-wrap" style="margin-top: 1rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:0.75rem;">
            <h3 style="margin:0;">Recent Audit Trail</h3>
            <div class="muted" style="display:flex; gap:0.6rem; font-size:0.9rem;">
                <a href="{{ route('admin.customers.index') }}" @if($auditAction === '') style="font-weight:700;" @endif>All</a>
                <a href="{{ route('admin.customers.index', ['audit_action' => 'created']) }}" @if($auditAction === 'created') style="font-weight:700;" @endif>Created</a>
                <a href="{{ route('admin.customers.index', ['audit_action' => 'updated']) }}" @if($auditAction === 'updated') style="font-weight:700;" @endif>Updated</a>
                <a href="{{ route('admin.customers.index', ['audit_action' => 'deleted']) }}" @if($auditAction === 'deleted') style="font-weight:700;" @endif>Deleted</a>
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
