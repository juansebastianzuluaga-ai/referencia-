<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIdentificationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('identification-types.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'alpha_dash:ascii', 'unique:identification_types,code'],
            'name' => ['required', 'string', 'max:120', 'unique:identification_types,name'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
