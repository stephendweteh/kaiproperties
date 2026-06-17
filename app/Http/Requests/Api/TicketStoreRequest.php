<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TicketStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'etd' => ['nullable', 'date'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
