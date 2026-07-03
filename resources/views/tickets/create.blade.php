@extends('layouts.app', ['title' => 'Create Ticket'])

@section('content')
    <h2>Log a Maintenance Ticket</h2>

    <form method="POST" action="{{ route('tickets.store') }}" class="card" enctype="multipart/form-data" data-loader-action="ticket-create">
        @csrf

        @include('tickets.partials.form-fields')

        <button type="submit" data-loader-action="ticket-create">Create Ticket</button>
    </form>
@endsection
