<?php

namespace Modules\Billing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Tenant\Models\Tenants;

class HandleInvoicePaidJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        $customerId = data_get($this->payload, 'data.object.customer');

        if (! $customerId) {
            return;
        }

        $tenant = Tenants::query()->where('stripe_id', $customerId)->first();

        if (! $tenant) {
            return;
        }

        $tenant->update([
            'billing_status' => 'active',
            'delinquent_since' => null,
        ]);

        Log::info('Queued invoice paid handler executed.', [
            'event_id' => data_get($this->payload, 'id'),
            'customer' => $customerId,
            'tenant_id' => $tenant->id,
        ]);
    }
}
