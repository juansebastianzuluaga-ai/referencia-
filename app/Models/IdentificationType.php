<?php

namespace App\Models;

use App\Models\Concerns\HasAuditableTags;
use Database\Factories\IdentificationTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable(['code', 'name', 'is_active'])]
class IdentificationType extends Model implements Auditable
{
    /** @use HasFactory<IdentificationTypeFactory> */
    use HasAuditableTags, HasFactory;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
