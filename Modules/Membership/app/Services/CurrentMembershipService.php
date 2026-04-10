<?php

namespace Modules\Membership\Services;

use Modules\Membership\Enums\TenantPermission;
use Modules\Membership\Models\Membership;
use Modules\Membership\Support\TenantRolePermissions;
use Modules\User\Models\User;

class CurrentMembershipService
{
    /**
     * Return the authenticated user's membership in the current tenant.
     * Throws ModelNotFoundException if the user is not a member.
     */
    public static function get(): Membership
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->memberships()->firstOrFail();
    }

    /**
     * Return the given user's membership in the current tenant, or null.
     */
    public static function for(User $user): ?Membership
    {
        return $user->memberships()->first();
    }

    /**
     * Check whether the given user's current-tenant role grants a permission.
     */
    public static function hasPermission(User $user, TenantPermission $permission): bool
    {
        $membership = static::for($user);

        if (! $membership) {
            return false;
        }

        return in_array(
            $membership->role->value,
            TenantRolePermissions::rolesWithPermission($permission),
            strict: true,
        );
    }
}
