<?php

namespace Modules\Billing\Listeners;

use Modules\Billing\Events\BillingInvoicePaid;
use Modules\Billing\Jobs\HandleInvoicePaidJob;

class DispatchInvoicePaidJob
{
    public function handle(BillingInvoicePaid $event): void
    {
        HandleInvoicePaidJob::dispatch($event->payload);
    }
}
