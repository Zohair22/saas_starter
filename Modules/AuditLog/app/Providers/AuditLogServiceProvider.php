<?php

namespace Modules\AuditLog\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\AuditLog\Interfaces\Contracts\AuditLogServiceInterface;
use Modules\AuditLog\Models\AuditLog;
use Modules\AuditLog\Policies\AuditLogPolicy;
use Modules\AuditLog\Services\AuditLogService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AuditLogServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'AuditLog';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'auditlog';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(AuditLogServiceInterface::class, AuditLogService::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(AuditLog::class, AuditLogPolicy::class);
    }
}
