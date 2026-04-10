<?php

namespace Modules\ActivityLog\Listeners;

use Modules\ActivityLog\Enums\ActivityAction;
use Modules\ActivityLog\Interfaces\Contracts\ActivityLogServiceInterface;
use Modules\Task\Events\TaskUpdated;

class LogTaskUpdated
{
    public function __construct(
        private readonly ActivityLogServiceInterface $activityLogService,
    ) {}

    public function handle(TaskUpdated $event): void
    {
        $this->activityLogService->log(
            tenantId: (int) $event->task->tenant_id,
            actor: $event->actor,
            action: ActivityAction::TaskUpdated,
            subject: $event->task,
            metadata: ['title' => $event->task->title, 'status' => $event->task->status],
        );
    }
}
