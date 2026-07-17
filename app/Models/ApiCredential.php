<?php

namespace App\Models;

use App\Models\Concerns\HasAuditableTags;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;

class ApiCredential extends Model implements Auditable
{
    use HasAuditableTags, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'api_key',
        'api_secret',
        'status',
        'expires_at',
        'last_used_at',
        'allowed_ips',
        'abilities',
        'rate_limit',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'allowed_ips' => 'array',
        'abilities' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'rate_limit' => 'integer',
    ];

    protected $hidden = [
        'api_secret',
    ];

    public function requestLogs()
    {
        return $this->hasMany(ApiRequestLog::class, 'api_credential_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function generateApiKey(): string
    {
        do {
            $apiKey = 'cacsb_'.Str::random(48);
        } while (self::where('api_key', $apiKey)->exists());

        return $apiKey;
    }

    public static function generateSecret(): string
    {
        return Str::random(64);
    }

    public function setApiSecretAttribute($value): void
    {
        $this->attributes['api_secret'] = Hash::make($value);
    }

    public function verifySecret(string $secret): bool
    {
        return Hash::check($secret, $this->api_secret);
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            $this->update(['status' => 'expired']);

            return false;
        }

        return true;
    }

    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true;
        }

        return in_array($ip, $this->allowed_ips);
    }

    public function hasAbility(string $ability): bool
    {
        if (empty($this->abilities) || in_array('*', $this->abilities)) {
            return true;
        }

        return in_array($ability, $this->abilities);
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function deactivate(): void
    {
        $this->update(['status' => 'inactive']);
    }

    public function revoke(): void
    {
        $this->update(['status' => 'revoked']);
    }
}
