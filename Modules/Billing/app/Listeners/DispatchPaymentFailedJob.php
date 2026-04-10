<?php

namespace Modules\Billing\Listeners;

use Modules\Billing\Events\BillingPaymentFailed;
use Modules\Billing\Jobs\HandlePaymentFailedJob;

class DispatchPaymentFailedJob
{
    public function handle(BillingPaymentFailed $event): void
    {
        HandlePaymentFailedJob::dispatch($event->payload);
    }
}
