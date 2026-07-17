<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'identification_type_id' => ['required', 'integer', 'exists:identification_types,id'],
            'identification_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('users')
                    ->where('identification_type_id', $this->integer('identification_type_id'))
                    ->ignore($userId),
            ],
            'user_name' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'user_name')->ignore($userId)],
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'sur_name' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'job_title' => ['nullable', 'string', 'max:250'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identification_type_id.required' => 'El tipo de identificación es obligatorio.',
            'identification_type_id.exists' => 'El tipo de identificación seleccionado no es válido.',
            'identification_number.required' => 'El número de identificación es obligatorio.',
            'identification_number.unique' => 'Este número de identificación ya está registrado.',
            'user_name.required' => 'El nombre de usuario es obligatorio.',
            'user_name.unique' => 'Este nombre de usuario ya está en uso.',
            'user_name.regex' => 'El nombre de usuario solo puede contener letras, números, puntos, guiones y guiones bajos.',
            'first_name.required' => 'El primer nombre es obligatorio.',
            'last_name.required' => 'El primer apellido es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
        ];
    }
}
