<?php

namespace Modules\Billing\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Models\Plan;
use Modules\Tenant\Models\Tenants;

class HandleSubscriptionCanceledJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        $customerId = data_get($this->payload, 'data.object.customer');
        $cancelAtPeriodEnd = (bool) data_get($this->payload, 'data.object.cancel_at_period_end', false);
        $currentPeriodEnd = data_get($this->payload, 'data.object.current_period_end');

        if (! $customerId) {
            return;
        }

        $tenant = Tenants::query()->where('stripe_id', $customerId)->first();

        if (! $tenant) {
            return;
        }

        $freePlan = Plan::query()->where('code', 'free')->first();

        if ($freePlan && ! $cancelAtPeriodEnd) {
            $tenant->update(['plan_id' => $freePlan->id]);
        }

        $tenant->update([
            'billing_status' => 'canceled',
            'grace_period_ends_at' => $currentPeriodEnd ? Carbon::createFromTimestamp((int) $currentPeriodEnd) : null,
        ]);

        if ($cancelAtPeriodEnd && $tenant->grace_period_ends_at) {
            EnforceGracePeriodDowngradeJob::dispatch($tenant->id)
                ->delay($tenant->grace_period_ends_at);
        }

        Log::info('Queued subscription canceled handler executed.', [
            'event_id' => data_get($this->payload, 'id'),
            'customer' => $customerId,
            'tenant_id' => $tenant->id,
            'plan_code' => $freePlan?->code,
            'cancel_at_period_end' => $cancelAtPeriodEnd,
        ]);
    }
}
