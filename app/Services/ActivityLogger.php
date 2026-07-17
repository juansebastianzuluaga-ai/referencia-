<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     * @param  array<int, string>  $tags
     */
    public function log(
        string $event,
        ?string $module = null,
        ?string $description = null,
        array $properties = [],
        array $tags = [],
        ?User $user = null,
    ): ActivityLog {
        $resolvedUser = $user ?? Auth::user();

        return ActivityLog::create([
            'user_id' => $resolvedUser?->id,
            'event' => $event,
            'module' => $module,
            'description' => $description,
            'properties' => $properties,
            'url' => Request::fullUrl(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'tags' => empty($tags) ? null : implode(',', $tags),
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @param  array<int, string>  $tags
     */
    public static function record(
        string $event,
        ?string $module = null,
        ?string $description = null,
        array $properties = [],
        array $tags = [],
        ?User $user = null,
    ): ActivityLog {
        return app(self::class)->log($event, $module, $description, $properties, $tags, $user);
    }
}
