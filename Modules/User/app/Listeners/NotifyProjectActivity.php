<?php

namespace Modules\User\Listeners;

use Modules\Project\Events\ProjectCreated;
use Modules\Project\Events\ProjectUpdated;
use Modules\User\Notifications\WorkspaceEventNotification;

class NotifyProjectActivity
{
    public function handle(ProjectCreated|ProjectUpdated $event): void
    {
        $project = $event->project;
        $tenant = $project->tenant;

        if (! $tenant) {
            return;
        }

        $recipients = $tenant->users()
            ->where('users.id', '!=', $event->actor->id)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $action = $event instanceof ProjectCreated ? 'created' : 'updated';

        $recipients->each->notify(new WorkspaceEventNotification([
            'category' => 'project',
            'action' => $action,
            'title' => 'Project '.($action === 'created' ? 'created' : 'updated'),
            'body' => sprintf('%s %s project "%s".', $event->actor->name, $action, $project->name),
            'tenant_id' => $project->tenant_id,
            'project_id' => $project->id,
            'actor_id' => $event->actor->id,
            'url' => '/app/projects/'.$project->id,
        ]));
    }
}
