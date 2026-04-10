<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class UserMultiTenantMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_belong_to_multiple_tenants_with_different_roles(): void
    {
        $user = User::factory()->create([
            'email' => 'john@company.com',
        ]);

        $acme = Tenants::query()->create([
            'name' => 'ACME',
            'slug' => 'acme-'.str()->random(5),
            'owner_id' => $user->id,
        ]);

        $nike = Tenants::query()->create([
            'name' => 'Nike',
            'slug' => 'nike-'.str()->random(5),
            'owner_id' => User::factory()->create()->id,
        ]);

        $tesla = Tenants::query()->create([
            'name' => 'Tesla',
            'slug' => 'tesla-'.str()->random(5),
            'owner_id' => User::factory()->create()->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $acme->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Owner->value,
        ]);

        Membership::query()->create([
            'tenant_id' => $nike->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Member->value,
        ]);

        Membership::query()->create([
            'tenant_id' => $tesla->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Admin->value,
        ]);

        $user->load(['memberships', 'tenants']);

        $this->assertCount(3, $user->memberships);
        $this->assertCount(3, $user->tenants);
        $this->assertDatabaseHas('memberships', [
            'tenant_id' => $acme->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Owner->value,
        ]);
        $this->assertDatabaseHas('memberships', [
            'tenant_id' => $nike->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Member->value,
        ]);
        $this->assertDatabaseHas('memberships', [
            'tenant_id' => $tesla->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Admin->value,
        ]);
    }
}
