<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApiCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('api-keys.create') || $this->user()?->hasPermission('api-keys.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'allowed_ips' => ['nullable', 'array'],
            'allowed_ips.*' => ['ip'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string'],
            'rate_limit' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'metadata' => ['nullable', 'array'],
        ];

        if ($this->isMethod('POST') && ! $id) {
            $rules['api_key'] = ['nullable', 'string', 'max:64', 'unique:api_credentials,api_key'];
            $rules['api_secret'] = ['nullable', 'string', 'min:32', 'max:128'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la API es obligatorio.',
            'name.max' => 'El nombre no puede exceder los 100 caracteres.',
            'expires_at.after' => 'La fecha de expiración debe ser posterior a ahora.',
            'allowed_ips.*.ip' => 'Cada IP debe tener un formato válido.',
            'rate_limit.min' => 'El límite de peticiones debe ser al menos 1.',
            'rate_limit.max' => 'El límite de peticiones no puede exceder 10000.',
            'api_key.unique' => 'Esta API Key ya está en uso.',
            'api_secret.min' => 'El Secret debe tener al menos 32 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => trim($this->name)]);
        }

        if ($this->has('rate_limit') && $this->rate_limit !== null) {
            $this->merge(['rate_limit' => (int) $this->rate_limit]);
        }
    }
}
