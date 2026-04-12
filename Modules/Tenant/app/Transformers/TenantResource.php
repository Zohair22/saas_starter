<?php

namespace Modules\Tenant\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $isGraceActive = $this->grace_period_ends_at && $this->grace_period_ends_at->isFuture();
        $isWriteLocked = ! $isGraceActive
            && in_array((string) ($this->billing_status ?? ''), ['past_due', 'canceled', 'downgraded', 'unpaid', 'incomplete'], true);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'owner_id' => $this->owner_id,
            'billing_status' => $this->billing_status,
            'grace_period_ends_at' => $this->grace_period_ends_at,
            'lifecycle' => [
                'is_grace_active' => $isGraceActive,
                'is_write_locked' => $isWriteLocked,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
