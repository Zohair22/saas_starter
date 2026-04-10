<?php

namespace Modules\Membership\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Membership\Services\RoleService;

class MembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isCurrentUser = (int) $this->user_id === (int) $request->user()?->id;

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'role' => $this->role,
            'role_flags' => $this->when($isCurrentUser, fn () => [
                'is_owner' => RoleService::isOwner(),
                'is_admin' => RoleService::isAdmin(),
                'is_member' => RoleService::isMember(),
            ]),
            'is_current_user' => $isCurrentUser,
            'user' => $this->whenLoaded('user'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
