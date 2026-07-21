<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalClinicDocumentResource extends JsonResource
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
            'document_type' => $this->document_type,
            'document_type_label' => $this->getDocumentTypeLabel(),
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'formatted_size' => $this->formatted_size,
            'mime_type' => $this->mime_type,
            'url' => $this->url,
            'uploaded_at' => $this->uploaded_at,
        ];
    }

    private function getDocumentTypeLabel(): string
    {
        return match ($this->document_type) {
            'rut' => 'RUT',
            'chamber_of_commerce' => 'Cámara de Comercio',
            'legal_rep_id' => 'Cédula del Representante Legal',
            'other' => 'Otro',
            default => 'Desconocido',
        };
    }
}
