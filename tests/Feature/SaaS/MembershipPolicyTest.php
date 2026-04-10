<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class MembershipPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_membership(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'owner_id' => $admin->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'role' => MembershipRole::Admin->value,
        ]);

        Sanctum::actingAs($admin);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/memberships', [
                'user_id' => $member->id,
                'role' => MembershipRole::Member->value,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('memberships', [
            'tenant_id' => $tenant->id,
            'user_id' => $member->id,
            'role' => MembershipRole::Member->value,
        ]);
    }

    public function test_member_cannot_create_membership(): void
    {
        $memberActor = User::factory()->create();
        $memberTarget = User::factory()->create();

        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'owner_id' => $memberActor->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $memberActor->id,
            'role' => MembershipRole::Member->value,
        ]);

        Sanctum::actingAs($memberActor);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/memberships', [
                'user_id' => $memberTarget->id,
                'role' => MembershipRole::Member->value,
            ]);

        $response->assertForbidden();
    }
}
