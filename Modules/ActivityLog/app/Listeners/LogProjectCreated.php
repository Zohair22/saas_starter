<?php

namespace Modules\ActivityLog\Listeners;

use Modules\ActivityLog\Enums\ActivityAction;
use Modules\ActivityLog\Interfaces\Contracts\ActivityLogServiceInterface;
use Modules\Project\Events\ProjectCreated;

class LogProjectCreated
{
    public function __construct(
        private readonly ActivityLogServiceInterface $activityLogService,
    ) {}

    public function handle(ProjectCreated $event): void
    {
        $this->activityLogService->log(
            tenantId: (int) $event->project->tenant_id,
            actor: $event->actor,
            action: ActivityAction::ProjectCreated,
            subject: $event->project,
            metadata: ['name' => $event->project->name],
        );
    }
}
