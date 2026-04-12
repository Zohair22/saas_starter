<?php

namespace Tests\Unit\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Enums\TenantPermission;
use Modules\Membership\Models\Membership;
use Modules\Membership\Services\CurrentMembershipService;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class CurrentMembershipServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_throws_when_user_has_no_membership(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->expectException(ModelNotFoundException::class);

        CurrentMembershipService::get();
    }

    public function test_for_returns_null_when_user_has_no_membership(): void
    {
        $user = User::factory()->create();

        $membership = CurrentMembershipService::for($user);

        $this->assertNull($membership);
    }

    public function test_for_returns_existing_membership_for_user(): void
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme-membership-current',
            'owner_id' => $user->id,
        ]);

        $createdMembership = Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Owner->value,
        ]);

        request()->attributes->set('tenant', $tenant);

        $membership = CurrentMembershipService::for($user);

        $this->assertNotNull($membership);
        $this->assertSame($createdMembership->id, $membership->id);
    }

    public function test_has_permission_returns_true_for_owner(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Owner);
        request()->attributes->set('tenant', $tenant);

        $allowed = CurrentMembershipService::hasPermission($user, TenantPermission::ManageBilling);

        $this->assertTrue($allowed);
    }

    public function test_has_permission_returns_false_for_member_when_permission_is_restricted(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Member);
        request()->attributes->set('tenant', $tenant);

        $allowed = CurrentMembershipService::hasPermission($user, TenantPermission::ManageBilling);

        $this->assertFalse($allowed);
    }
}
