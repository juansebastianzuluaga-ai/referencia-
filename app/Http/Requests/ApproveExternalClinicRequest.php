<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveExternalClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('external-clinics.approve') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'change_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
