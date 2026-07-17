<?php

namespace App\Actions\Fortify;

use App\Models\Setting;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', $this->passwordRule(), 'confirmed'];
    }

    protected function passwordRule(): Password
    {
        $minLength = (int) Setting::get('password_min_length', 8);
        $requireSpecial = (bool) Setting::get('password_require_special', true);
        $requireNumbers = (bool) Setting::get('password_require_numbers', true);
        $requireUppercase = (bool) Setting::get('password_require_uppercase', true);

        $rule = Password::min($minLength);

        if ($requireUppercase) {
            $rule = $rule->mixedCase();
        }

        if ($requireNumbers) {
            $rule = $rule->numbers();
        }

        if ($requireSpecial) {
            $rule = $rule->symbols();
        }

        return $rule;
    }
}
