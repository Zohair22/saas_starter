<?php

namespace Modules\AuditLog\Listeners;

use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Interfaces\Contracts\AuditLogServiceInterface;
use Modules\Project\Events\ProjectUpdated;

class LogProjectUpdatedAudit
{
    public function __construct(
        private readonly AuditLogServiceInterface $auditLogService,
    ) {}

    public function handle(ProjectUpdated $event): void
    {
        $this->auditLogService->record(
            action: AuditAction::ProjectUpdated,
            tenantId: (int) $event->project->tenant_id,
            actor: $event->actor,
            subject: $event->project,
            newValues: [
                'name' => $event->project->name,
                'description' => $event->project->description,
            ],
        );
    }
}
