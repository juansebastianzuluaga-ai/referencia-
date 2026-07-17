<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiCredentialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'api_key' => $this->api_key,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toISOString(),
            'last_used_at' => $this->last_used_at?->toISOString(),
            'allowed_ips' => $this->allowed_ips,
            'abilities' => $this->abilities,
            'rate_limit' => $this->rate_limit,
            'metadata' => $this->metadata,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'request_logs_count' => $this->whenCounted('requestLogs'),
        ];
    }
}
