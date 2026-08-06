<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketStoreRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'property_id' => ['required', 'exists:properties,id'],
            'maintenance_category_id' => ['required', 'exists:maintenance_categories,id'],
            'unit' => ['nullable', 'string', 'max:100'],
            'reported_by' => ['nullable', 'exists:users,id'],
            'assigned_to' => ['nullable', 'array'],
            'assigned_to.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_TECHNICIAN)),
            ],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'etd' => ['nullable', 'date'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'estimated_cost_currency' => ['nullable', 'in:GBP,USD,EUR,GHS,CNY', 'required_with:estimated_cost'],
        ];
    }
}
