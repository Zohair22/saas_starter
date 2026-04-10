<?php

namespace Modules\Billing\Listeners;

use Modules\Billing\Events\BillingSubscriptionChanged;
use Modules\Billing\Jobs\HandleSubscriptionChangedJob;

class DispatchSubscriptionChangedJob
{
    public function handle(BillingSubscriptionChanged $event): void
    {
        HandleSubscriptionChangedJob::dispatch($event->payload);
    }
}
