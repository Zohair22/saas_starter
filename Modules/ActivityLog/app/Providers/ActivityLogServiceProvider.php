<?php

namespace Modules\ActivityLog\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\ActivityLog\Interfaces\Contracts\ActivityLogServiceInterface;
use Modules\ActivityLog\Models\ActivityLog;
use Modules\ActivityLog\Policies\ActivityLogPolicy;
use Modules\ActivityLog\Services\ActivityLogService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ActivityLogServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'ActivityLog';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'activitylog';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(ActivityLogServiceInterface::class, ActivityLogService::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);
    }
}
