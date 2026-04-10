<?php

namespace Tests\Unit;

use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Enums\TenantPermission;
use Modules\Membership\Support\TenantRolePermissions;
use Tests\TestCase;

class TenantRolePermissionsTest extends TestCase
{
    public function test_members_cannot_manage_billing(): void
    {
        $roles = TenantRolePermissions::rolesWithPermission(TenantPermission::ManageBilling);

        $this->assertNotContains(MembershipRole::Member->value, $roles);
        $this->assertContains(MembershipRole::Owner->value, $roles);
        $this->assertContains(MembershipRole::Admin->value, $roles);
    }

    public function test_members_can_view_memberships(): void
    {
        $roles = TenantRolePermissions::rolesWithPermission(TenantPermission::ViewMemberships);

        $this->assertContains(MembershipRole::Member->value, $roles);
        $this->assertContains(MembershipRole::Owner->value, $roles);
        $this->assertContains(MembershipRole::Admin->value, $roles);
    }
}
