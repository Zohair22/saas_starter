<?php

namespace Modules\Membership\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Membership\Enums\MembershipRole;
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
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

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

    /**
     * @return array<string, bool>
     */
    public static function capabilitiesFor(User $user): array
    {
        $membership = static::for($user);

        if (! $membership) {
            return [
                'is_tenant_member' => false,
                'can_view_memberships' => false,
                'can_view_billing' => false,
                'can_manage_projects' => false,
                'can_manage_memberships' => false,
                'can_manage_invitations' => false,
                'can_manage_billing' => false,
                'can_manage_tenant_settings' => false,
                'is_tenant_owner' => false,
            ];
        }

        $permissions = TenantRolePermissions::permissionsForRole($membership->role);

        return [
            'is_tenant_member' => true,
            'can_view_memberships' => in_array(TenantPermission::ViewMemberships->value, $permissions, true),
            'can_view_billing' => true,
            'can_manage_projects' => in_array(TenantPermission::ManageProjects->value, $permissions, true),
            'can_manage_memberships' => in_array(TenantPermission::ManageMemberships->value, $permissions, true),
            'can_manage_invitations' => in_array(TenantPermission::ManageInvitations->value, $permissions, true),
            'can_manage_billing' => in_array(TenantPermission::ManageBilling->value, $permissions, true),
            'can_manage_tenant_settings' => in_array(TenantPermission::ManageTenantSettings->value, $permissions, true),
            'is_tenant_owner' => $membership->role === MembershipRole::Owner,
        ];
    }
}
