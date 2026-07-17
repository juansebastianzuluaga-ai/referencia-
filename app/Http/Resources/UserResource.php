<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'identification_type' => IdentificationTypeResource::make($this->whenLoaded('identificationType')),
            'identification_number' => $this->identification_number,
            'user_name' => $this->user_name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'sur_name' => $this->sur_name,
            'full_name' => trim(implode(' ', array_filter([
                $this->first_name,
                $this->middle_name,
                $this->last_name,
                $this->sur_name,
            ]))),
            'email' => $this->email,
            'job_title' => $this->job_title,
            'is_active' => $this->is_active,
            'must_change_password' => $this->must_change_password,
            'must_update_profile' => $this->must_update_profile,
            'last_login_at' => $this->last_login_at,
            'failed_login_attempts' => $this->failed_login_attempts,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
