<?php

namespace Modules\Membership\Policies;

use Modules\Membership\Enums\TenantPermission;
use Modules\Membership\Models\Membership;
use Modules\Membership\Services\CurrentMembershipService;
use Modules\User\Models\User;

class MembershipPolicy
{
    public function viewAny(User $user): bool
    {
        return CurrentMembershipService::for($user) !== null;
    }

    public function view(User $user, Membership $membership): bool
    {
        $tenantId = (int) data_get(request()->attributes->get('tenant'), 'id');

        if ((int) $membership->tenant_id !== $tenantId) {
            return false;
        }

        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageTenantMembers($user);
    }

    public function update(User $user, Membership $membership): bool
    {
        return $this->view($user, $membership) && $this->canManageTenantMembers($user);
    }

    public function delete(User $user, Membership $membership): bool
    {
        return $this->view($user, $membership) && $this->canManageTenantMembers($user);
    }

    private function canManageTenantMembers(User $user): bool
    {
        return CurrentMembershipService::hasPermission($user, TenantPermission::ManageMemberships);
    }
}
