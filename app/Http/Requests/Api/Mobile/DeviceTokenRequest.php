<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class DeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'       => ['required', 'string', 'max:500'],
            'platform'    => ['required', 'in:android,ios,web'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
