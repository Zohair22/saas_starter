<?php

namespace Modules\ActivityLog\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\ActivityLog\Listeners\LogProjectCreated;
use Modules\ActivityLog\Listeners\LogProjectDeleted;
use Modules\ActivityLog\Listeners\LogProjectUpdated;
use Modules\ActivityLog\Listeners\LogTaskCompleted;
use Modules\ActivityLog\Listeners\LogTaskCreated;
use Modules\ActivityLog\Listeners\LogTaskUpdated;
use Modules\Project\Events\ProjectCreated;
use Modules\Project\Events\ProjectDeleted;
use Modules\Project\Events\ProjectUpdated;
use Modules\Task\Events\TaskCompleted;
use Modules\Task\Events\TaskCreated;
use Modules\Task\Events\TaskUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ProjectCreated::class => [LogProjectCreated::class],
        ProjectUpdated::class => [LogProjectUpdated::class],
        ProjectDeleted::class => [LogProjectDeleted::class],
        TaskCreated::class => [LogTaskCreated::class],
        TaskUpdated::class => [LogTaskUpdated::class],
        TaskCompleted::class => [LogTaskCompleted::class],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
