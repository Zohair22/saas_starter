<?php

namespace Modules\Billing\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Laravel\Cashier\Subscription;
use Modules\Billing\Interfaces\Contracts\BillingRepositoryInterface;
use Modules\Billing\Models\Plan;
use Modules\Tenant\Models\Tenants;

class BillingRepository implements BillingRepositoryInterface
{
    public function listPlans(): Collection
    {
        return Plan::query()->where('is_active', true)->orderBy('id')->get();
    }

    public function findPlanByCode(string $planCode): Plan
    {
        return Plan::query()->where('code', $planCode)->firstOrFail();
    }

    public function currentSubscription(Tenants $tenant): ?Subscription
    {
        return $tenant->subscription('default');
    }

    public function subscribe(Tenants $tenant, Plan $plan, ?string $paymentMethod): ?Subscription
    {
        $tenant->update(['plan_id' => $plan->id]);

        if (! $plan->stripe_price_id) {
            return null;
        }

        return $tenant
            ->newSubscription('default', $plan->stripe_price_id)
            ->create($paymentMethod);
    }

    public function swap(Tenants $tenant, Plan $plan): Subscription
    {
        $subscription = $tenant->subscription('default');
        $subscription->swap($plan->stripe_price_id);

        $tenant->update(['plan_id' => $plan->id]);

        return $subscription->fresh();
    }

    public function cancel(Tenants $tenant): void
    {
        $subscription = $tenant->subscription('default');

        if ($subscription) {
            $subscription->cancel();
        }
    }
}
