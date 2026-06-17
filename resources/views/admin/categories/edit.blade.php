@extends('layouts.app', ['title' => 'Edit Category'])

@section('content')
    <h2>Edit Category</h2>
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="card">
        @csrf
        @method('PUT')
        @include('admin.categories.partials.form-fields', ['category' => $category])
        <button type="submit">Update</button>
    </form>
@endsection
