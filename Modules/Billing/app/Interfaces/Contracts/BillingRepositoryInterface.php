<?php

namespace Modules\Billing\Interfaces\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Laravel\Cashier\Subscription;
use Modules\Billing\Models\Plan;
use Modules\Tenant\Models\Tenants;

interface BillingRepositoryInterface
{
    public function listPlans(): Collection;

    public function findPlanByCode(string $planCode): Plan;

    public function currentSubscription(Tenants $tenant): ?Subscription;

    public function subscribe(Tenants $tenant, Plan $plan, ?string $paymentMethod): ?Subscription;

    public function swap(Tenants $tenant, Plan $plan): Subscription;

    public function cancel(Tenants $tenant): void;
}
