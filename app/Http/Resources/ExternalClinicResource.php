<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalClinicResource extends JsonResource
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
            'nit' => $this->nit,
            'business_name' => $this->business_name,
            'trade_name' => $this->trade_name,

            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,

            'address' => $this->address,
            'city' => $this->city,
            'department' => $this->department,

            'legal_rep_name' => $this->legal_rep_name,
            'legal_rep_id_type' => IdentificationTypeResource::make($this->whenLoaded('legalRepIdentificationType')),
            'legal_rep_id_number' => $this->legal_rep_id_number,

            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'rejection_reason' => $this->rejection_reason,

            'approved_by' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver->id,
                'full_name' => $this->approver->full_name,
            ]),
            'approved_at' => $this->approved_at,

            'rejected_by' => $this->whenLoaded('rejecter', fn () => [
                'id' => $this->rejecter->id,
                'full_name' => $this->rejecter->full_name,
            ]),
            'rejected_at' => $this->rejected_at,

            'must_change_password' => $this->must_change_password,
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'failed_login_attempts' => $this->failed_login_attempts,

            'documents' => ExternalClinicDocumentResource::collection($this->whenLoaded('documents')),
            'requests' => ExternalClinicRequestResource::collection($this->whenLoaded('requests')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            'active' => 'Activa',
            'inactive' => 'Inactiva',
            default => 'Desconocido',
        };
    }
}
