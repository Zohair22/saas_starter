<?php

namespace Modules\Tenant\Providers;

// use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Membership\Enums\TenantPermission;
use Modules\Membership\Models\Membership;
use Modules\Membership\Support\TenantRolePermissions;
use Modules\Tenant\Interfaces\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Interfaces\Contracts\TenantServiceInterface;
use Modules\Tenant\Models\Tenants;
use Modules\Tenant\Repositories\TenantRepository;
use Modules\Tenant\Services\TenantService;
use Modules\User\Models\User;
use Nwidart\Modules\Support\ModuleServiceProvider;

class TenantServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Tenant';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'tenant';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }

    /**
     * Register module bindings.
     */
    public function register(): void
    {
        parent::register();

        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(TenantServiceInterface::class, TenantService::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::define('manageTenantSettings', function (User $user, Tenants $tenant): bool {
            $membership = Membership::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $membership) {
                return false;
            }

            return in_array(
                $membership->role->value,
                TenantRolePermissions::rolesWithPermission(TenantPermission::ManageTenantSettings),
                true,
            );
        });

        Gate::define('deleteTenant', function (User $user, Tenants $tenant): bool {
            return (int) $tenant->owner_id === (int) $user->id;
        });

        Gate::define('transferOwnership', function (User $user, Tenants $tenant): bool {
            return (int) $tenant->owner_id === (int) $user->id;
        });
    }
}
