<?php

namespace Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Modules\Billing\Classes\DTOs\CreateSubscriptionData;
use Modules\Billing\Classes\DTOs\SwapSubscriptionData;
use Modules\Billing\Http\Requests\SubscribeTenantRequest;
use Modules\Billing\Http\Requests\SwapSubscriptionRequest;
use Modules\Billing\Interfaces\Contracts\BillingServiceInterface;
use Modules\Billing\Transformers\PlanResource;
use Modules\Tenant\Models\Tenants;
use Symfony\Component\HttpFoundation\Response;

class BillingController extends Controller
{
    public function __construct(
        private readonly BillingServiceInterface $billingService,
    ) {}

    public function index(): JsonResponse
    {
        /** @var Tenants $tenant */
        $tenant = request()->attributes->get('tenant');
        $this->authorize('viewPlans', $tenant);

        $plans = $this->billingService->listPlans();
        $subscription = $this->billingService->currentSubscription($tenant);

        return response()->json([
            'plans' => PlanResource::collection($plans),
            'subscription' => $subscription,
        ]);
    }

    public function store(SubscribeTenantRequest $request): JsonResponse
    {
        /** @var Tenants $tenant */
        $tenant = $request->attributes->get('tenant');
        $this->authorize('manageSubscription', $tenant);

        try {
            $subscription = $this->billingService->subscribe($tenant, CreateSubscriptionData::fromRequest($request));

            return response()->json([
                'message' => 'Subscription created.',
                'subscription' => $subscription,
            ], Response::HTTP_CREATED);
        } catch (IncompletePayment $exception) {
            return response()->json([
                'message' => 'Payment requires additional action.',
                'payment_id' => $exception->payment->id,
            ], Response::HTTP_PAYMENT_REQUIRED);
        }
    }

    public function update(SwapSubscriptionRequest $request): JsonResponse
    {
        /** @var Tenants $tenant */
        $tenant = $request->attributes->get('tenant');
        $this->authorize('manageSubscription', $tenant);
        $subscription = $this->billingService->swap($tenant, SwapSubscriptionData::fromRequest($request));

        return response()->json([
            'message' => 'Subscription swapped.',
            'subscription' => $subscription,
        ]);
    }

    public function destroy(): JsonResponse
    {
        /** @var Tenants $tenant */
        $tenant = request()->attributes->get('tenant');
        $this->authorize('manageSubscription', $tenant);
        $this->billingService->cancel($tenant);

        return response()->json([
            'message' => 'Subscription cancellation scheduled.',
        ]);
    }
}
