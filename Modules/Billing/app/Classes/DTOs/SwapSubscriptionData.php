<?php

namespace Modules\Billing\Classes\DTOs;

use Modules\Billing\Http\Requests\SwapSubscriptionRequest;

class SwapSubscriptionData
{
    public function __construct(
        public readonly string $planCode,
    ) {}

    public static function fromRequest(SwapSubscriptionRequest $request): self
    {
        return new self(
            planCode: $request->validated('plan_code'),
        );
    }
}
