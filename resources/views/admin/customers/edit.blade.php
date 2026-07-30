@extends('layouts.app', ['title' => 'Edit Customer'])

@section('content')
    <h2>Edit Customer</h2>
    <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="card">
        @csrf
        @method('PUT')
        @include('admin.customers.partials.form-fields', ['customer' => $customer])
        <button type="submit">Update</button>
    </form>
@endsection
