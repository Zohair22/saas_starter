<?php

namespace Modules\ActivityLog\Services;

use Modules\ActivityLog\Enums\ActivityAction;
use Modules\ActivityLog\Interfaces\Contracts\ActivityLogServiceInterface;
use Modules\ActivityLog\Models\ActivityLog;
use Modules\User\Models\User;

class ActivityLogService implements ActivityLogServiceInterface
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        int $tenantId,
        User $actor,
        ActivityAction $action,
        ?object $subject = null,
        array $metadata = [],
    ): void {
        ActivityLog::query()->create([
            'tenant_id' => $tenantId,
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id ?? null,
            'metadata' => empty($metadata) ? null : $metadata,
        ]);
    }
}
