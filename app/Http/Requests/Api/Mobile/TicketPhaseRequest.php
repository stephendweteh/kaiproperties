<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class TicketPhaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phase_name'        => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'technician_notes'  => ['nullable', 'string'],
        ];
    }
}
