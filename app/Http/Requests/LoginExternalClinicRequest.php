<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginExternalClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nit' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }
}
