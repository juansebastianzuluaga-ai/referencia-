<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'identification_type_id' => ['required', 'integer', 'exists:identification_types,id'],
            'identification_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('users')->where('identification_type_id', $input['identification_type_id'] ?? null),
            ],
            'user_name' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique(User::class)],
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'sur_name' => ['nullable', 'string', 'max:80'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'identification_type_id' => $input['identification_type_id'],
            'identification_number' => $input['identification_number'],
            'user_name' => $input['user_name'],
            'first_name' => $input['first_name'],
            'middle_name' => $input['middle_name'] ?? null,
            'last_name' => $input['last_name'],
            'sur_name' => $input['sur_name'] ?? null,
            'email' => $input['email'],
            'job_title' => $input['job_title'] ?? null,
            'must_change_password' => true,
            'must_update_profile' => true,
            'password' => Hash::make($input['password']),
        ]);
    }
}
