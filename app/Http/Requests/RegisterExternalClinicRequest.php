<?php

namespace App\Http\Requests;

use App\Actions\Fortify\PasswordValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterExternalClinicRequest extends FormRequest
{
    use PasswordValidationRules;

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
            'nit' => ['required', 'string', 'max:20', 'regex:/^[0-9-]+$/', 'unique:external_clinics,nit'],
            'business_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],

            'email' => ['required', 'email', 'max:255', 'unique:external_clinics,email'],
            'phone' => ['required', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],

            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'department' => ['required', 'string', 'max:100'],

            'legal_rep_name' => ['required', 'string', 'max:255'],
            'legal_rep_id_type_id' => ['required', 'integer', 'exists:identification_types,id'],
            'legal_rep_id_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('external_clinics')->where('legal_rep_id_type_id', $this->integer('legal_rep_id_type_id')),
            ],

            'password' => $this->passwordRules(),

            'documents' => ['sometimes', 'array', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],

            'terms_accepted' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nit.regex' => 'El NIT solo debe contener números y guiones.',
            'nit.unique' => 'El NIT ya está registrado en el sistema.',
            'email.unique' => 'El correo electrónico ya está registrado.',
            'legal_rep_id_number.unique' => 'El número de identificación del representante legal ya está registrado.',
            'documents.*.max' => 'Cada documento no debe superar los 5MB.',
            'documents.*.mimes' => 'Los documentos deben ser PDF, JPG o PNG.',
            'terms_accepted.accepted' => 'Debe aceptar los términos y condiciones para continuar.',
        ];
    }
}
