<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIdentificationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('identification-types.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $identificationTypeId = $this->route('identification_type')?->id ?? $this->route('identificationType')?->id;

        return [
            'code' => ['sometimes', 'string', 'max:20', 'alpha_dash:ascii', Rule::unique('identification_types', 'code')->ignore($identificationTypeId)],
            'name' => ['sometimes', 'string', 'max:120', Rule::unique('identification_types', 'name')->ignore($identificationTypeId)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
