<?php

namespace Modules\AuditLog\Policies;

use Modules\Membership\Enums\TenantPermission;
use Modules\Membership\Services\CurrentMembershipService;
use Modules\User\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return CurrentMembershipService::hasPermission($user, TenantPermission::ManageBilling);
    }
}
