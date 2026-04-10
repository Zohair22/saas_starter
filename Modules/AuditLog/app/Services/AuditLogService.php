<?php

namespace Modules\AuditLog\Services;

use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Interfaces\Contracts\AuditLogServiceInterface;
use Modules\AuditLog\Models\AuditLog;
use Modules\User\Models\User;

class AuditLogService implements AuditLogServiceInterface
{
    public function record(
        AuditAction $action,
        ?int $tenantId = null,
        ?User $actor = null,
        ?object $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        AuditLog::query()->create([
            'tenant_id' => $tenantId,
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }
}
