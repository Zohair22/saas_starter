<?php

namespace Modules\Task\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Task\Interfaces\Contracts\TaskRepositoryInterface;
use Modules\Task\Interfaces\Contracts\TaskServiceInterface;
use Modules\Task\Models\Task;
use Modules\Task\Policies\TaskPolicy;
use Modules\Task\Repositories\TaskRepository;
use Modules\Task\Services\TaskService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class TaskServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Task';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'task';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(TaskServiceInterface::class, TaskService::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Task::class, TaskPolicy::class);
    }
}
