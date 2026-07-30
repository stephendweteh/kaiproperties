@extends('layouts.app', ['title' => 'Create Ticket'])

@section('content')
    <h2>Log a Maintenance Ticket</h2>

    <form method="POST" action="{{ route('tickets.store') }}" class="card" enctype="multipart/form-data" data-loader-action="ticket-create">
        @csrf

        @include('tickets.partials.form-fields')

        <button type="submit" data-loader-action="ticket-create">Create Ticket</button>
    </form>

    <script>
        const customerSelect = document.getElementById('customer_id');
        const propertySelect = document.getElementById('property_id');

        if (customerSelect && propertySelect) {
            const allPropertyOptions = Array.from(propertySelect.options).slice(1);

            customerSelect.addEventListener('change', function() {
                const selectedCustomerId = this.value;
                const customerOption = Array.from(customerSelect.options).find(opt => opt.value === selectedCustomerId);
                const propertyIds = customerOption ? (customerOption.dataset.properties || '').split(',').filter(id => id) : [];

                propertySelect.innerHTML = '<option value="">Select Property</option>';

                allPropertyOptions.forEach(option => {
                    if (!selectedCustomerId || propertyIds.includes(option.value)) {
                        propertySelect.appendChild(option.cloneNode(true));
                    }
                });
            });

            const selectedCustomerId = customerSelect.value;
            if (selectedCustomerId) {
                customerSelect.dispatchEvent(new Event('change'));
            }
        }
    </script>
@endsection
