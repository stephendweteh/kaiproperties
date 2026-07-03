@extends('layouts.app', ['title' => 'Create Customer'])

@section('content')
    <h2>Create Customer</h2>
    <form method="POST" action="{{ route('admin.customers.store') }}" class="card">
        @csrf
        @include('admin.customers.partials.form-fields')
        <button type="submit">Create</button>
    </form>
@endsection
