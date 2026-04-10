<?php

namespace Modules\Billing\Classes\DTOs;

use Modules\Billing\Http\Requests\SubscribeTenantRequest;

class CreateSubscriptionData
{
    public function __construct(
        public readonly string $planCode,
        public readonly ?string $paymentMethod,
    ) {}

    public static function fromRequest(SubscribeTenantRequest $request): self
    {
        return new self(
            planCode: $request->validated('plan_code'),
            paymentMethod: $request->validated('payment_method'),
        );
    }
}
