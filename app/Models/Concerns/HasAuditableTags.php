<?php

namespace App\Models\Concerns;

use OwenIt\Auditing\Auditable;

trait HasAuditableTags
{
    use Auditable;

    /**
     * @var array<int, string>
     */
    public array $auditCustomTags = [];

    /**
     * @return array<int, string>
     */
    public function generateTags(): array
    {
        return $this->auditCustomTags;
    }
}
