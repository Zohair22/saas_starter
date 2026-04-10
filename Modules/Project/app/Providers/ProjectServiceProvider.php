<?php

namespace Modules\Project\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Project\Interfaces\Contracts\ProjectRepositoryInterface;
use Modules\Project\Interfaces\Contracts\ProjectServiceInterface;
use Modules\Project\Models\Project;
use Modules\Project\Policies\ProjectPolicy;
use Modules\Project\Repositories\ProjectRepository;
use Modules\Project\Services\ProjectService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ProjectServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Project';

    protected string $nameLower = 'project';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->bind(ProjectServiceInterface::class, ProjectService::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Project::class, ProjectPolicy::class);
    }
}
