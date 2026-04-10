<?php

namespace Modules\Billing\Events;

class BillingSubscriptionCanceled
{
    public function __construct(
        public readonly array $payload,
    ) {}
}
