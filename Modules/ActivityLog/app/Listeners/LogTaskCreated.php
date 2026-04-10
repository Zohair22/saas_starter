<?php

namespace Modules\ActivityLog\Listeners;

use Modules\ActivityLog\Enums\ActivityAction;
use Modules\ActivityLog\Interfaces\Contracts\ActivityLogServiceInterface;
use Modules\Task\Events\TaskCreated;

class LogTaskCreated
{
    public function __construct(
        private readonly ActivityLogServiceInterface $activityLogService,
    ) {}

    public function handle(TaskCreated $event): void
    {
        $this->activityLogService->log(
            tenantId: (int) $event->task->tenant_id,
            actor: $event->actor,
            action: ActivityAction::TaskCreated,
            subject: $event->task,
            metadata: ['title' => $event->task->title, 'project_id' => $event->task->project_id],
        );
    }
}
