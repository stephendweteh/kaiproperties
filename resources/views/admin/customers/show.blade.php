@extends('layouts.app', ['title' => 'Customer Details'])

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:1rem;">
        <div>
            <h2 style="margin:0 0 0.35rem;">{{ $customer->name }}</h2>
            <p class="muted" style="margin:0;">Customer details and linked properties.</p>
        </div>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <a class="btn btn-alt" href="{{ route('admin.customers.edit', $customer) }}">Edit</a>
            <a class="btn" href="{{ route('admin.customers.index') }}">Back to Customers</a>
        </div>
    </div>

    <section class="card" style="margin-bottom:1rem;">
        <div class="form-grid" style="margin-bottom:0;">
            <div>
                <div class="muted">Name</div>
                <div>{{ $customer->name }}</div>
            </div>
            <div>
                <div class="muted">Email</div>
                <div>{{ $customer->email ?: '-' }}</div>
            </div>
            <div>
                <div class="muted">Phone</div>
                <div>{{ $customer->phone ?: '-' }}</div>
            </div>
            <div>
                <div class="muted">Status</div>
                <div>{{ $customer->is_active ? 'Active' : 'Inactive' }}</div>
            </div>
            <div style="grid-column: 1 / -1;">
                <div class="muted">Address</div>
                <div>{{ $customer->address ?: '-' }}</div>
            </div>
        </div>
    </section>

    <section class="card">
        <h3 style="margin-top:0;">Properties</h3>

        @if($customer->properties->isEmpty())
            <p class="muted" style="margin:0;">No properties linked to this customer yet.</p>
        @else
            <ul style="margin:0; padding-left:1.2rem;">
                @foreach($customer->properties as $property)
                    <li style="margin-bottom:0.55rem;">
                        <strong>{{ $property->name }}</strong>
                        @if($property->code)
                            <span class="muted">({{ $property->code }})</span>
                        @endif
                        <div class="muted" style="font-size:0.9rem;">
                            {{ trim(($property->city ?: '').' '.($property->state ?: '')) ?: ($property->address ?: 'No location details') }}
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
