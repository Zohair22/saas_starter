<?php

namespace Modules\Billing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Models\Plan;
use Modules\Tenant\Models\Tenants;

class HandleSubscriptionChangedJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        $customerId = data_get($this->payload, 'data.object.customer');
        $priceId = data_get($this->payload, 'data.object.items.data.0.price.id');

        if (! $customerId || ! $priceId) {
            return;
        }

        $tenant = Tenants::query()->where('stripe_id', $customerId)->first();

        if (! $tenant) {
            return;
        }

        $plan = Plan::query()->where('stripe_price_id', $priceId)->first();

        if (! $plan) {
            return;
        }

        $previousPlanId = $tenant->plan_id;
        $tenant->update([
            'plan_id' => $plan->id,
            'billing_status' => 'active',
            'delinquent_since' => null,
        ]);

        Log::info('Queued subscription changed handler executed.', [
            'event_id' => data_get($this->payload, 'id'),
            'customer' => $customerId,
            'tenant_id' => $tenant->id,
            'previous_plan_id' => $previousPlanId,
            'current_plan_id' => $plan->id,
            'stripe_price_id' => $priceId,
        ]);
    }
}
