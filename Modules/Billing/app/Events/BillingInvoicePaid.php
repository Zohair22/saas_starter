<?php

namespace Modules\Billing\Events;

class BillingInvoicePaid
{
    public function __construct(
        public readonly array $payload,
    ) {}
}
