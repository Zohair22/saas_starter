<?php

namespace Modules\AuditLog\Listeners;

use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Interfaces\Contracts\AuditLogServiceInterface;
use Modules\Task\Events\TaskUpdated;

class LogTaskUpdatedAudit
{
    public function __construct(
        private readonly AuditLogServiceInterface $auditLogService,
    ) {}

    public function handle(TaskUpdated $event): void
    {
        $this->auditLogService->record(
            action: AuditAction::TaskUpdated,
            tenantId: (int) $event->task->tenant_id,
            actor: $event->actor,
            subject: $event->task,
            newValues: [
                'title' => $event->task->title,
                'status' => $event->task->status?->value,
                'priority' => $event->task->priority?->value,
                'assigned_to' => $event->task->assigned_to,
            ],
        );
    }
}
