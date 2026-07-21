<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectExternalClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('external-clinics.reject') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Debe indicar el motivo del rechazo.',
            'rejection_reason.min' => 'El motivo debe tener al menos 10 caracteres.',
        ];
    }
}
