<?php

namespace Modules\Membership\Policies;

use Modules\Membership\Enums\TenantPermission;
use Modules\Membership\Models\Invitation;
use Modules\Membership\Services\CurrentMembershipService;
use Modules\User\Models\User;

class InvitationPolicy
{
    public function create(User $user): bool
    {
        return $this->canManageTenantMembers($user);
    }

    public function delete(User $user, Invitation $invitation): bool
    {
        $tenantId = (int) data_get(request()->attributes->get('tenant'), 'id');

        if ((int) $invitation->tenant_id !== $tenantId) {
            return false;
        }

        return $this->canManageTenantMembers($user);
    }

    private function canManageTenantMembers(User $user): bool
    {
        return CurrentMembershipService::hasPermission($user, TenantPermission::ManageInvitations);
    }
}
