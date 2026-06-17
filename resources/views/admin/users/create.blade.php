@extends('layouts.app', ['title' => 'Create User'])

@section('content')
    <h2>Create User</h2>
    <form method="POST" action="{{ route('admin.users.store') }}" class="card" enctype="multipart/form-data">
        @csrf
        @include('admin.users.partials.form-fields', ['roles' => $roles])
        <button type="submit">Create</button>
    </form>
@endsection
