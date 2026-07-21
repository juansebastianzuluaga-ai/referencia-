<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalClinicRequest extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'external_clinic_id',
        'previous_status',
        'new_status',
        'changed_by',
        'change_reason',
        'ip_address',
        'user_agent',
    ];

    public function externalClinic(): BelongsTo
    {
        return $this->belongsTo(ExternalClinic::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
