<?php

namespace App\Models;

use App\Models\Concerns\HasAuditableTags;
use Database\Factories\ExternalClinicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'nit',
    'business_name',
    'trade_name',
    'email',
    'phone',
    'mobile',
    'address',
    'city',
    'department',
    'legal_rep_name',
    'legal_rep_id_type_id',
    'legal_rep_id_number',
    'password',
    'status',
    'rejection_reason',
    'approved_by',
    'approved_at',
    'rejected_by',
    'rejected_at',
    'must_change_password',
    'email_verified_at',
    'last_login_at',
    'failed_login_attempts',
])]
#[Hidden(['password', 'remember_token'])]
class ExternalClinic extends Authenticatable implements Auditable
{
    /** @use HasFactory<ExternalClinicFactory> */
    use HasApiTokens, HasAuditableTags, HasFactory, Notifiable, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $auditExclude = [
        'password',
        'remember_token',
        'failed_login_attempts',
    ];

    public function legalRepIdentificationType(): BelongsTo
    {
        return $this->belongsTo(IdentificationType::class, 'legal_rep_id_type_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ExternalClinicRequest::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ExternalClinicDocument::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function approve(User $user): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    public function reject(User $user, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function deactivate(): void
    {
        $this->update(['status' => 'inactive']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    protected function casts(): array
    {
        return [
            'must_change_password' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'failed_login_attempts' => 'integer',
            'password' => 'hashed',
        ];
    }
}
