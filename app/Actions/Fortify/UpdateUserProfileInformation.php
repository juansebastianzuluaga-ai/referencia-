<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'identification_type_id' => ['required', 'integer', 'exists:identification_types,id'],
            'identification_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('users')
                    ->where('identification_type_id', $input['identification_type_id'] ?? null)
                    ->ignore($user->id),
            ],
            'user_name' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'user_name')->ignore($user->id)],
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'sur_name' => ['nullable', 'string', 'max:80'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ])->validateWithBag('updateProfileInformation');

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'identification_type_id' => $input['identification_type_id'],
                'identification_number' => $input['identification_number'],
                'user_name' => $input['user_name'],
                'first_name' => $input['first_name'],
                'middle_name' => $input['middle_name'] ?? null,
                'last_name' => $input['last_name'],
                'sur_name' => $input['sur_name'] ?? null,
                'email' => $input['email'],
                'job_title' => $input['job_title'] ?? null,
                'must_update_profile' => false,
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'identification_type_id' => $input['identification_type_id'],
            'identification_number' => $input['identification_number'],
            'user_name' => $input['user_name'],
            'first_name' => $input['first_name'],
            'middle_name' => $input['middle_name'] ?? null,
            'last_name' => $input['last_name'],
            'sur_name' => $input['sur_name'] ?? null,
            'email' => $input['email'],
            'email_verified_at' => null,
            'job_title' => $input['job_title'] ?? null,
            'must_update_profile' => false,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
