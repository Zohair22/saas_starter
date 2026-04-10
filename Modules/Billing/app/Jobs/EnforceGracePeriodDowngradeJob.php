<?php

namespace Modules\Billing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Models\Plan;
use Modules\Tenant\Models\Tenants;

class EnforceGracePeriodDowngradeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $tenantId,
    ) {}

    public function handle(): void
    {
        $tenant = Tenants::query()->find($this->tenantId);

        if (! $tenant || ! $tenant->grace_period_ends_at) {
            return;
        }

        if ($tenant->grace_period_ends_at->isFuture()) {
            return;
        }

        $freePlan = Plan::query()->where('code', 'free')->first();

        if (! $freePlan) {
            return;
        }

        $tenant->update([
            'plan_id' => $freePlan->id,
            'billing_status' => 'downgraded',
            'grace_period_ends_at' => null,
        ]);

        Log::info('Grace-period downgrade enforced.', [
            'tenant_id' => $tenant->id,
            'plan_id' => $freePlan->id,
        ]);
    }
}
