<?php

namespace Modules\Billing\Policies;

use Modules\Membership\Enums\TenantPermission;
use Modules\Membership\Services\CurrentMembershipService;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;

class BillingPolicy
{
    public function viewPlans(User $user, Tenants $tenant): bool
    {
        return $this->isCurrentTenant($tenant) && $this->isTenantMember($user);
    }

    public function manageSubscription(User $user, Tenants $tenant): bool
    {
        if (! $this->isCurrentTenant($tenant)) {
            return false;
        }

        return CurrentMembershipService::hasPermission($user, TenantPermission::ManageBilling);
    }

    private function isTenantMember(User $user): bool
    {
        return CurrentMembershipService::for($user) !== null;
    }

    private function isCurrentTenant(Tenants $tenant): bool
    {
        return (int) $tenant->id === (int) data_get(request()->attributes->get('tenant'), 'id');
    }
}
