<?php

namespace Modules\Membership\Support;

use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Enums\TenantPermission;

class TenantRolePermissions
{
    /**
     * @var array<string, list<string>>
     */
    private const MATRIX = [
        MembershipRole::Owner->value => [
            TenantPermission::ViewMemberships->value,
            TenantPermission::ManageMemberships->value,
            TenantPermission::ManageInvitations->value,
            TenantPermission::ManageProjects->value,
            TenantPermission::ManageBilling->value,
        ],
        MembershipRole::Admin->value => [
            TenantPermission::ViewMemberships->value,
            TenantPermission::ManageMemberships->value,
            TenantPermission::ManageInvitations->value,
            TenantPermission::ManageProjects->value,
            TenantPermission::ManageBilling->value,
        ],
        MembershipRole::Member->value => [
            TenantPermission::ViewMemberships->value,
        ],
    ];

    /**
     * @return list<string>
     */
    public static function rolesWithPermission(TenantPermission $permission): array
    {
        $roles = [];

        foreach (self::MATRIX as $role => $permissions) {
            if (in_array($permission->value, $permissions, true)) {
                $roles[] = $role;
            }
        }

        return $roles;
    }
}
