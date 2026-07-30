@extends('layouts.app', ['title' => 'Create Category'])

@section('content')
    <h2>Create Category</h2>
    <form method="POST" action="{{ route('admin.categories.store') }}" class="card">
        @csrf
        @include('admin.categories.partials.form-fields')
        <button type="submit">Create</button>
    </form>
@endsection
