<?php

namespace Modules\Membership\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Membership\Models\Invitation;

/** @mixin Invitation */
class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'tenant_name' => data_get($this->tenant, 'name'),
            'email' => $this->email,
            'role' => data_get($this->role, 'value', $this->role),
            'token' => $this->token,
            'expires_at' => $this->expires_at,
            'accepted_at' => $this->accepted_at,
            'revoked_at' => $this->revoked_at,
            'invited_by' => $this->invited_by,
            'invited_by_name' => data_get($this->inviter, 'name'),
        ];
    }
}
