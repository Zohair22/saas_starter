<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Services\RoleService;
use Tests\TestCase;

class RoleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_owner_returns_true_for_owner(): void
    {
        [$tenant, $owner] = $this->createTenantWithMember(MembershipRole::Owner);
        request()->attributes->set('tenant', $tenant);
        $this->actingAs($owner);

        $this->assertTrue(RoleService::isOwner());
    }

    public function test_is_owner_returns_false_for_admin(): void
    {
        [$tenant, $admin] = $this->createTenantWithMember(MembershipRole::Admin);
        request()->attributes->set('tenant', $tenant);
        $this->actingAs($admin);

        $this->assertFalse(RoleService::isOwner());
    }

    public function test_is_admin_returns_true_for_owner_and_admin(): void
    {
        [$ownerTenant, $owner] = $this->createTenantWithMember(MembershipRole::Owner);
        request()->attributes->set('tenant', $ownerTenant);
        $this->actingAs($owner);
        $this->assertTrue(RoleService::isAdmin());

        [$adminTenant, $admin] = $this->createTenantWithMember(MembershipRole::Admin);
        request()->attributes->set('tenant', $adminTenant);
        $this->actingAs($admin);
        $this->assertTrue(RoleService::isAdmin());
    }

    public function test_is_admin_returns_false_for_member(): void
    {
        [$tenant, $member] = $this->createTenantWithMember(MembershipRole::Member);
        request()->attributes->set('tenant', $tenant);
        $this->actingAs($member);

        $this->assertFalse(RoleService::isAdmin());
    }

    public function test_is_member_returns_true_only_for_member(): void
    {
        [$memberTenant, $member] = $this->createTenantWithMember(MembershipRole::Member);
        request()->attributes->set('tenant', $memberTenant);
        $this->actingAs($member);
        $this->assertTrue(RoleService::isMember());

        [$ownerTenant, $owner] = $this->createTenantWithMember(MembershipRole::Owner);
        request()->attributes->set('tenant', $ownerTenant);
        $this->actingAs($owner);
        $this->assertFalse(RoleService::isMember());
    }
}
