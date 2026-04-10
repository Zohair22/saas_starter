<?php

namespace Modules\Billing\Interfaces\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Laravel\Cashier\Subscription;
use Modules\Billing\Classes\DTOs\CreateSubscriptionData;
use Modules\Billing\Classes\DTOs\SwapSubscriptionData;
use Modules\Tenant\Models\Tenants;

interface BillingServiceInterface
{
    public function listPlans(): Collection;

    public function currentSubscription(Tenants $tenant): ?Subscription;

    public function subscribe(Tenants $tenant, CreateSubscriptionData $data): ?Subscription;

    public function swap(Tenants $tenant, SwapSubscriptionData $data): Subscription;

    public function cancel(Tenants $tenant): void;
}
