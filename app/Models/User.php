<?php

namespace App\Models;

use App\Models\Concerns\HasAuditableTags;
use App\Notifications\ResetPasswordNotification;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'identification_type_id',
    'identification_number',
    'user_name',
    'first_name',
    'middle_name',
    'last_name',
    'sur_name',
    'email',
    'job_title',
    'password',
    'is_active',
    'must_change_password',
    'must_update_profile',
    'last_login_at',
    'failed_login_attempts',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasAuditableTags, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $auditExclude = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function identificationType(): BelongsTo
    {
        return $this->belongsTo(IdentificationType::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        return $this->can($permission);
    }

    protected function fullName(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => trim(implode(' ', array_filter([
                $this->first_name,
                $this->middle_name,
                $this->last_name,
                $this->sur_name,
            ]))),
        );
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
            'must_update_profile' => 'boolean',
            'failed_login_attempts' => 'integer',
            'password' => 'hashed',
        ];
    }
}
