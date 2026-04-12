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
use Stripe\Exception\InvalidRequestException;

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

        $currentSubscription = $this->billingRepository->currentSubscription($tenant);

        if (! $currentSubscription) {
            throw ValidationException::withMessages([
                'subscription' => ['No active subscription found to swap.'],
            ]);
        }

        if ($currentSubscription->hasIncompletePayment()) {
            $latestPayment = $currentSubscription->latestPayment();
            $paymentId = $latestPayment?->id;
            $message = 'The current subscription payment is incomplete. Complete payment confirmation before changing plans.';

            if ($paymentId) {
                $message .= " Payment ID: {$paymentId}.";
            }

            throw ValidationException::withMessages([
                'subscription' => [$message],
            ]);
        }

        try {
            return $this->billingRepository->swap($tenant, $plan);
        } catch (InvalidRequestException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'payment is incomplete')) {
                throw ValidationException::withMessages([
                    'subscription' => ['The current subscription payment is incomplete. Complete payment confirmation before changing plans.'],
                ]);
            }

            throw $exception;
        }
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
