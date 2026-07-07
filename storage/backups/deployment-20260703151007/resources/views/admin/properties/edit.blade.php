@extends('layouts.app', ['title' => 'Edit Property'])

@section('content')
    <h2>Edit Property</h2>
    <form method="POST" action="{{ route('admin.properties.update', $property) }}" class="card">
        @csrf
        @method('PUT')
        @include('admin.properties.partials.form-fields', ['property' => $property])
        <button type="submit">Update</button>
    </form>
@endsection
