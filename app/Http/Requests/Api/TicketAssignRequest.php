<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketAssignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $assignedTo = $this->input('assigned_to');

        if ($assignedTo === null || $assignedTo === '') {
            return;
        }

        $assignedTo = is_array($assignedTo) ? $assignedTo : [$assignedTo];

        $this->merge([
            'assigned_to' => array_values(array_filter($assignedTo, fn ($value) => $value !== null && $value !== '')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => ['required', 'array', 'min:1'],
            'assigned_to.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_TECHNICIAN)),
            ],
        ];
    }
}
