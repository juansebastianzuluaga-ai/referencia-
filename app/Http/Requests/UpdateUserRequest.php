<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'identification_type_id' => ['sometimes', 'integer', 'exists:identification_types,id'],
            'identification_number' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('users')
                    ->where('identification_type_id', $this->integer('identification_type_id', $this->route('user')->identification_type_id))
                    ->ignore($userId),
            ],
            'user_name' => ['sometimes', 'string', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'user_name')->ignore($userId)],
            'first_name' => ['sometimes', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['sometimes', 'string', 'max:80'],
            'sur_name' => ['nullable', 'string', 'max:80'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'job_title' => ['nullable', 'string', 'max:250'],
            'password' => ['sometimes', 'string', 'min:10', 'confirmed'],
            'is_active' => ['sometimes', 'boolean'],
            'must_change_password' => ['sometimes', 'boolean'],
            'must_update_profile' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }
}
