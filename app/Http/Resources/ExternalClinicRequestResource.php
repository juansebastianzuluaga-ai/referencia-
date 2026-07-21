<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalClinicRequestResource extends JsonResource
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
            'previous_status' => $this->previous_status,
            'new_status' => $this->new_status,
            'new_status_label' => $this->getStatusLabel($this->new_status),
            'change_reason' => $this->change_reason,
            'changed_by' => $this->whenLoaded('changedBy', fn () => [
                'id' => $this->changedBy->id,
                'full_name' => $this->changedBy->full_name,
            ]),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at,
        ];
    }

    private function getStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Pendiente',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            'active' => 'Activa',
            'inactive' => 'Inactiva',
            default => 'Desconocido',
        };
    }
}
