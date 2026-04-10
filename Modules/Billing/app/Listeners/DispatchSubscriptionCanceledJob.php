<?php

namespace Modules\Billing\Listeners;

use Modules\Billing\Events\BillingSubscriptionCanceled;
use Modules\Billing\Jobs\HandleSubscriptionCanceledJob;

class DispatchSubscriptionCanceledJob
{
    public function handle(BillingSubscriptionCanceled $event): void
    {
        HandleSubscriptionCanceledJob::dispatch($event->payload);
    }
}
