@extends('layouts.app', ['title' => 'Create User'])

@section('content')
    <h2>Create User</h2>
    <form method="POST" action="{{ route('admin.users.store') }}" class="card" enctype="multipart/form-data" data-loader-action="user-create">
        @csrf
        @include('admin.users.partials.form-fields', ['roles' => $roles])
        <button type="submit" data-loader-action="user-create">Create</button>
    </form>
@endsection
