<?php

namespace App\Models;

use App\Models\Concerns\HasAuditableTags;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable(['key', 'value', 'group', 'type'])]
class Setting extends Model implements Auditable
{
    use HasAuditableTags, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    public static function set(string $key, mixed $value): void
    {
        $setting = self::where('key', $key)->first();

        if ($setting) {
            $setting->update(['value' => self::encodeValue($value, $setting->type)]);
        } else {
            self::create([
                'key' => $key,
                'value' => self::encodeValue($value, 'string'),
                'group' => 'general',
                'type' => 'string',
            ]);
        }
    }

    public static function getGroup(string $group): Collection
    {
        return self::where('group', $group)->get();
    }

    public static function getGroupArray(string $group): array
    {
        return self::where('group', $group)
            ->get()
            ->mapWithKeys(fn (self $setting) => [$setting->key => self::castValue($setting->value, $setting->type)])
            ->toArray();
    }

    public static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    public static function encodeValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            'json' => json_encode($value),
            default => (string) $value,
        };
    }
}
