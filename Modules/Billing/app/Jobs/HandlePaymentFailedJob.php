<?php

namespace Modules\Billing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Tenant\Models\Tenants;

class HandlePaymentFailedJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        $customerId = data_get($this->payload, 'data.object.customer');

        if ($customerId) {
            $tenant = Tenants::query()->where('stripe_id', $customerId)->first();

            if ($tenant) {
                $tenant->update([
                    'billing_status' => 'past_due',
                    'delinquent_since' => $tenant->delinquent_since ?? now(),
                ]);
            }
        }

        Log::warning('Queued billing payment failure handler executed.', [
            'event_id' => data_get($this->payload, 'id'),
            'customer' => $customerId,
            'invoice' => data_get($this->payload, 'data.object.id'),
        ]);
    }
}
