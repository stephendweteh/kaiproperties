@extends('layouts.app', ['title' => 'Edit User'])

@section('content')
    <h2>Edit User</h2>
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="card" enctype="multipart/form-data" data-loader-action="user-update">
        @csrf
        @method('PUT')
        @include('admin.users.partials.form-fields', ['user' => $user, 'roles' => $roles])
        <button type="submit" data-loader-action="user-update">Update</button>
    </form>
@endsection
