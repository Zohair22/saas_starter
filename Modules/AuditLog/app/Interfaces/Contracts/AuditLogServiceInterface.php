<?php

namespace Modules\AuditLog\Interfaces\Contracts;

use Modules\AuditLog\Enums\AuditAction;
use Modules\User\Models\User;

interface AuditLogServiceInterface
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(
        AuditAction $action,
        ?int $tenantId = null,
        ?User $actor = null,
        ?object $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void;
}
