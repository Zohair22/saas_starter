<?php

namespace Modules\User\Listeners;

use Modules\Billing\Events\BillingInvoicePaid;
use Modules\Billing\Events\BillingPaymentFailed;
use Modules\Billing\Events\BillingSubscriptionCanceled;
use Modules\Billing\Events\BillingSubscriptionChanged;
use Modules\Tenant\Models\Tenants;
use Modules\User\Notifications\WorkspaceEventNotification;

class NotifyBillingActivity
{
    public function handle(BillingPaymentFailed|BillingInvoicePaid|BillingSubscriptionChanged|BillingSubscriptionCanceled $event): void
    {
        $customerId = data_get($event->payload, 'data.object.customer');

        if (! is_string($customerId) || $customerId === '') {
            return;
        }

        $tenant = Tenants::query()->where('stripe_id', $customerId)->first();

        if (! $tenant) {
            return;
        }

        $recipients = $tenant->users()->get();

        if ($recipients->isEmpty()) {
            return;
        }

        [$action, $title, $body] = $this->messageForEvent($event);

        $recipients->each->notify(new WorkspaceEventNotification([
            'category' => 'billing',
            'action' => $action,
            'title' => $title,
            'body' => $body,
            'tenant_id' => $tenant->id,
            'url' => '/app/billing',
        ]));
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function messageForEvent(BillingPaymentFailed|BillingInvoicePaid|BillingSubscriptionChanged|BillingSubscriptionCanceled $event): array
    {
        return match (true) {
            $event instanceof BillingPaymentFailed => ['payment_failed', 'Payment failed', 'A recent billing payment failed. Review billing details.'],
            $event instanceof BillingInvoicePaid => ['invoice_paid', 'Invoice paid', 'A billing invoice was paid successfully.'],
            $event instanceof BillingSubscriptionCanceled => ['subscription_canceled', 'Subscription canceled', 'The workspace subscription was canceled.'],
            default => ['subscription_changed', 'Subscription changed', 'The workspace subscription details were updated.'],
        };
    }
}
