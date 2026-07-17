<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CurrentUserController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        return $this->sendResponse(
            UserResource::make($request->user()->load(['identificationType', 'roles.permissions']))
        );
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->forceFill([
            'identification_type_id' => $data['identification_type_id'],
            'identification_number' => $data['identification_number'],
            'user_name' => $data['user_name'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'sur_name' => $data['sur_name'] ?? null,
            'email' => $data['email'],
            'job_title' => $data['job_title'] ?? null,
            'must_update_profile' => false,
        ])->save();

        return $this->sendResponse(
            UserResource::make($user->load(['identificationType', 'roles.permissions'])),
            'Perfil actualizado correctamente'
        );
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        return $this->sendResponse(
            UserResource::make($user->load(['identificationType', 'roles.permissions'])),
            'Contraseña actualizada correctamente'
        );
    }

    public function passwordPolicy(): JsonResponse
    {
        return $this->sendResponse([
            'min_length' => (int) Setting::get('password_min_length', 8),
            'require_special' => (bool) Setting::get('password_require_special', true),
            'require_numbers' => (bool) Setting::get('password_require_numbers', true),
            'require_uppercase' => (bool) Setting::get('password_require_uppercase', true),
        ]);
    }
}
