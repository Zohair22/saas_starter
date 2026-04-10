<?php

namespace Modules\AuditLog\Listeners;

use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Interfaces\Contracts\AuditLogServiceInterface;
use Modules\Project\Events\ProjectDeleted;

class LogProjectDeletedAudit
{
    public function __construct(
        private readonly AuditLogServiceInterface $auditLogService,
    ) {}

    public function handle(ProjectDeleted $event): void
    {
        $this->auditLogService->record(
            action: AuditAction::ProjectDeleted,
            tenantId: $event->tenantId,
            actor: $event->actor,
            oldValues: [
                'project_id' => $event->projectId,
                'project_name' => $event->projectName,
            ],
        );
    }
}
