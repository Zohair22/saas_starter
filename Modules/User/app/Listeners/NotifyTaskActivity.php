<?php

namespace Modules\User\Listeners;

use Modules\Task\Events\TaskCompleted;
use Modules\Task\Events\TaskCreated;
use Modules\Task\Events\TaskUpdated;
use Modules\User\Notifications\WorkspaceEventNotification;

class NotifyTaskActivity
{
    public function handle(TaskCreated|TaskUpdated|TaskCompleted $event): void
    {
        $task = $event->task;
        $tenant = $task->tenant;

        if (! $tenant) {
            return;
        }

        $recipients = $tenant->users()
            ->where('users.id', '!=', $event->actor->id)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $action = match (true) {
            $event instanceof TaskCreated => 'created',
            $event instanceof TaskCompleted => 'completed',
            default => 'updated',
        };

        $recipients->each->notify(new WorkspaceEventNotification([
            'category' => 'task',
            'action' => $action,
            'title' => 'Task '.($action === 'completed' ? 'completed' : $action),
            'body' => sprintf('%s %s task "%s".', $event->actor->name, $action, $task->title),
            'tenant_id' => $task->tenant_id,
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'actor_id' => $event->actor->id,
            'url' => '/app/projects/'.$task->project_id.'/tasks/'.$task->id,
        ]));
    }
}
