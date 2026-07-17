<?php

namespace App\Services;

use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Events\AuditCustom;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<int, string>  $tags
     */
    public function log(
        Auditable $auditable,
        string $event,
        array $oldValues = [],
        array $newValues = [],
        array $tags = [],
    ): void {
        $auditable->auditEvent = $event;
        $auditable->auditCustomOld = $oldValues;
        $auditable->auditCustomNew = $newValues;
        $auditable->auditCustomTags = $tags;
        $auditable->isCustomEvent = true;

        Event::dispatch(new AuditCustom($auditable));

        $auditable->auditCustomOld = null;
        $auditable->auditCustomNew = null;
        $auditable->auditCustomTags = [];
        $auditable->isCustomEvent = false;
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<int, string>  $tags
     */
    public static function record(
        Auditable $auditable,
        string $event,
        array $oldValues = [],
        array $newValues = [],
        array $tags = [],
    ): void {
        app(self::class)->log($auditable, $event, $oldValues, $newValues, $tags);
    }
}
