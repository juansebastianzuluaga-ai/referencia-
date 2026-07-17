<?php

namespace App\Models;

use App\Models\Concerns\HasAuditableTags;
use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Models\Permission as SpatiePermission;

#[Fillable(['name', 'guard_name', 'display_name', 'description'])]
class Permission extends SpatiePermission implements Auditable
{
    /** @use HasFactory<PermissionFactory> */
    use HasAuditableTags, HasFactory;
}
