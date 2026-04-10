<?php

namespace Modules\Billing\Events;

class BillingSubscriptionChanged
{
    public function __construct(
        public readonly array $payload,
    ) {}
}
