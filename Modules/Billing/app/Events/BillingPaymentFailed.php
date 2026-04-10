<?php

namespace Modules\Billing\Events;

class BillingPaymentFailed
{
    public function __construct(
        public readonly array $payload,
    ) {}
}
