@extends('layouts.app', ['title' => 'Edit Ticket'])

@section('content')
    <h2>Edit Ticket {{ $ticket->ticket_no }}</h2>

    <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="card" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('tickets.partials.form-fields', ['editMode' => true])

        <button type="submit">Update Ticket</button>
    </form>
@endsection
