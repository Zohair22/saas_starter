<?php

namespace Modules\Billing\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Laravel\Cashier\Subscription;
use Modules\Billing\Classes\DTOs\CreateSubscriptionData;
use Modules\Billing\Classes\DTOs\SwapSubscriptionData;
use Modules\Billing\Interfaces\Contracts\BillingRepositoryInterface;
use Modules\Billing\Interfaces\Contracts\BillingServiceInterface;
use Modules\Billing\Models\Plan;
use Modules\Tenant\Models\Tenants;

class BillingService implements BillingServiceInterface
{
    public function __construct(
        private readonly BillingRepositoryInterface $billingRepository,
    ) {}

    public function listPlans(): Collection
    {
        return $this->billingRepository->listPlans();
    }

    public function currentSubscription(Tenants $tenant): ?Subscription
    {
        return $this->billingRepository->currentSubscription($tenant);
    }

    /**
     * @throws IncompletePayment
     */
    public function subscribe(Tenants $tenant, CreateSubscriptionData $data): ?Subscription
    {
        $plan = $this->billingRepository->findPlanByCode($data->planCode);
        $this->ensurePlanSupportsCurrentUsage($tenant, $plan);

        return $this->billingRepository->subscribe($tenant, $plan, $data->paymentMethod);
    }

    public function swap(Tenants $tenant, SwapSubscriptionData $data): Subscription
    {
        $plan = $this->billingRepository->findPlanByCode($data->planCode);
        $this->ensurePlanSupportsCurrentUsage($tenant, $plan);

        return $this->billingRepository->swap($tenant, $plan);
    }

    public function cancel(Tenants $tenant): void
    {
        $this->billingRepository->cancel($tenant);
    }

    private function ensurePlanSupportsCurrentUsage(Tenants $tenant, Plan $plan): void
    {
        $currentUsers = $tenant->memberships()->count();
        $currentProjects = $tenant->projects()->count();

        if ($currentUsers > (int) $plan->max_users) {
            throw ValidationException::withMessages([
                'plan_code' => ['Current tenant users exceed selected plan limits.'],
            ]);
        }

        if ($currentProjects > (int) $plan->max_projects) {
            throw ValidationException::withMessages([
                'plan_code' => ['Current tenant projects exceed selected plan limits.'],
            ]);
        }
    }
}
