<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'api_credential_id',
        'endpoint',
        'method',
        'ip_address',
        'user_agent',
        'request_data',
        'response_status',
        'response_time',
        'error_message',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_status' => 'integer',
        'response_time' => 'integer',
        'created_at' => 'datetime',
    ];

    public function apiCredential()
    {
        return $this->belongsTo(ApiCredential::class, 'api_credential_id');
    }

    public function scopeSuccess($query)
    {
        return $query->whereBetween('response_status', [200, 299]);
    }

    public function scopeErrors($query)
    {
        return $query->where('response_status', '>=', 400);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeLastHours($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
