@extends('layouts.app', ['title' => 'Create Property'])

@section('content')
    <h2>Create Property</h2>
    <form method="POST" action="{{ route('admin.properties.store') }}" class="card">
        @csrf
        @include('admin.properties.partials.form-fields')
        <button type="submit">Create</button>
    </form>
@endsection
