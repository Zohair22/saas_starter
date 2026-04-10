<?php

namespace Modules\AuditLog\Listeners;

use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Interfaces\Contracts\AuditLogServiceInterface;
use Modules\Task\Events\TaskCompleted;

class LogTaskCompletedAudit
{
    public function __construct(
        private readonly AuditLogServiceInterface $auditLogService,
    ) {}

    public function handle(TaskCompleted $event): void
    {
        $this->auditLogService->record(
            action: AuditAction::TaskCompleted,
            tenantId: (int) $event->task->tenant_id,
            actor: $event->actor,
            subject: $event->task,
            newValues: [
                'title' => $event->task->title,
                'status' => $event->task->status?->value,
            ],
        );
    }
}
