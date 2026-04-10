<?php

namespace Modules\Billing\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'max_users' => $this->max_users,
            'max_projects' => $this->max_projects,
            'api_rate_limit' => $this->api_rate_limit,
            'is_paid' => filled($this->stripe_price_id),
        ];
    }
}
