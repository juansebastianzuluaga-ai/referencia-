<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExternalClinicProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('external_clinic') !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var \App\Models\ExternalClinic|null $clinic */
        $clinic = $this->user('external_clinic');
        $clinicId = $clinic?->id;

        return [
            'trade_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'string', 'max:500'],
            'city' => ['sometimes', 'string', 'max:100'],
            'department' => ['sometimes', 'string', 'max:100'],

            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('external_clinics', 'email')->ignore($clinicId),
            ],

            'legal_rep_name' => ['sometimes', 'string', 'max:255'],
            'legal_rep_id_type_id' => ['sometimes', 'integer', 'exists:identification_types,id'],
            'legal_rep_id_number' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('external_clinics')->where('legal_rep_id_type_id', $this->integer('legal_rep_id_type_id'))->ignore($clinicId),
            ],
        ];
    }
}
