<?php

namespace Modules\Billing\Http\Controllers;

use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Modules\Billing\Events\BillingInvoicePaid;
use Modules\Billing\Events\BillingPaymentFailed;
use Modules\Billing\Events\BillingSubscriptionCanceled;
use Modules\Billing\Events\BillingSubscriptionChanged;
use Modules\Billing\Models\WebhookReceipt;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    public function handleInvoicePaymentFailed(array $payload): Response
    {
        if (! $this->shouldProcess($payload)) {
            return $this->successMethod();
        }

        event(new BillingPaymentFailed($payload));

        return $this->successMethod();
    }

    public function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        if (! $this->shouldProcess($payload)) {
            return $this->successMethod();
        }

        event(new BillingSubscriptionCanceled($payload));

        return $this->successMethod();
    }

    public function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        if (! $this->shouldProcess($payload)) {
            return $this->successMethod();
        }

        event(new BillingSubscriptionChanged($payload));

        return $this->successMethod();
    }

    public function handleInvoicePaid(array $payload): Response
    {
        if (! $this->shouldProcess($payload)) {
            return $this->successMethod();
        }

        event(new BillingInvoicePaid($payload));

        return $this->successMethod();
    }

    private function shouldProcess(array $payload): bool
    {
        $eventId = (string) data_get($payload, 'id');

        if ($eventId === '') {
            return true;
        }

        $alreadyProcessed = WebhookReceipt::query()->where('stripe_event_id', $eventId)->exists();

        if ($alreadyProcessed) {
            return false;
        }

        WebhookReceipt::query()->create([
            'stripe_event_id' => $eventId,
            'type' => (string) data_get($payload, 'type', 'unknown'),
            'payload_hash' => hash('sha256', (string) json_encode($payload)),
            'processed_at' => now(),
        ]);

        return true;
    }
}
