<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
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
        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => ['required', 'string', $this->passwordRule(), 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Debe ingresar su contraseña actual.',
            'current_password.current_password' => 'La contraseña actual no coincide con la registrada.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
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
