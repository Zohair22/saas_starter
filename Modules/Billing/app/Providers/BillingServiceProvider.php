<?php

namespace Modules\Billing\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Cashier\Cashier;
use Modules\Billing\Console\UsageCountersRolloverCommand;
use Modules\Billing\Interfaces\Contracts\BillingRepositoryInterface;
use Modules\Billing\Interfaces\Contracts\BillingServiceInterface;
use Modules\Billing\Interfaces\Contracts\FeatureLimitServiceInterface;
use Modules\Billing\Interfaces\Contracts\UsageCounterServiceInterface;
use Modules\Billing\Policies\BillingPolicy;
use Modules\Billing\Repositories\BillingRepository;
use Modules\Billing\Services\BillingService;
use Modules\Billing\Services\FeatureLimitService;
use Modules\Billing\Services\UsageCounterService;
use Modules\Tenant\Models\Tenants;
use Nwidart\Modules\Support\ModuleServiceProvider;

class BillingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Billing';

    protected string $nameLower = 'billing';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->commands([
            UsageCountersRolloverCommand::class,
        ]);

        $this->app->bind(BillingRepositoryInterface::class, BillingRepository::class);
        $this->app->bind(BillingServiceInterface::class, BillingService::class);
        $this->app->bind(FeatureLimitServiceInterface::class, FeatureLimitService::class);
        $this->app->bind(UsageCounterServiceInterface::class, UsageCounterService::class);
    }

    public function boot(): void
    {
        parent::boot();

        Cashier::useCustomerModel(Tenants::class);
        Gate::policy(Tenants::class, BillingPolicy::class);
    }
}
