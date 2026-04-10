<?php

namespace Modules\ActivityLog\Listeners;

use Modules\ActivityLog\Enums\ActivityAction;
use Modules\ActivityLog\Interfaces\Contracts\ActivityLogServiceInterface;
use Modules\Project\Events\ProjectDeleted;

class LogProjectDeleted
{
    public function __construct(
        private readonly ActivityLogServiceInterface $activityLogService,
    ) {}

    public function handle(ProjectDeleted $event): void
    {
        $this->activityLogService->log(
            tenantId: $event->tenantId,
            actor: $event->actor,
            action: ActivityAction::ProjectDeleted,
            metadata: ['project_id' => $event->projectId, 'name' => $event->projectName],
        );
    }
}
