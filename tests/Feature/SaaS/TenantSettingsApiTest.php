<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class TenantSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_tenant_creates_owner_membership_and_lists_it_for_owner(): void
    {
        $owner = User::factory()->create();

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/tenants', [
            'name' => 'Acme Create',
            'slug' => 'acme-create',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Acme Create')
            ->assertJsonPath('data.slug', 'acme-create')
            ->assertJsonPath('data.owner_id', $owner->id);

        $tenantId = (int) $response->json('data.id');

        $this->assertDatabaseHas('memberships', [
            'tenant_id' => $tenantId,
            'user_id' => $owner->id,
            'role' => MembershipRole::Owner->value,
        ]);

        $this->getJson('/api/v1/tenants')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $tenantId);
    }

    public function test_admin_can_update_tenant_name_and_slug(): void
    {
        [$tenant, $admin] = $this->createTenantWithMember(MembershipRole::Admin);

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/v1/tenants/{$tenant->id}", [
            'name' => 'Acme Updated',
            'slug' => 'acme-updated',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Acme Updated')
            ->assertJsonPath('data.slug', 'acme-updated');
    }

    public function test_member_cannot_update_tenant_settings(): void
    {
        [$tenant, $member] = $this->createTenantWithMember(MembershipRole::Member);

        Sanctum::actingAs($member);

        $response = $this->patchJson("/api/v1/tenants/{$tenant->id}", [
            'name' => 'Blocked Update',
            'slug' => 'blocked-update',
        ]);

        $response->assertForbidden();
    }

    public function test_owner_can_transfer_ownership_to_existing_member(): void
    {
        $owner = User::factory()->create([
            'password' => 'password',
        ]);

        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme-owner-transfer',
            'owner_id' => $owner->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::Owner->value,
        ]);

        $admin = User::factory()->create();
        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'role' => MembershipRole::Admin->value,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/tenants/{$tenant->id}/transfer-ownership", [
            'new_owner_id' => $admin->id,
            'password' => 'password',
        ]);

        $response->assertOk();
        $this->assertSame($admin->id, (int) $tenant->fresh()->owner_id);

        $this->assertDatabaseHas('memberships', [
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'role' => MembershipRole::Owner->value,
        ]);
    }

    public function test_owner_can_delete_tenant_with_current_password(): void
    {
        $owner = User::factory()->create([
            'password' => 'password',
        ]);

        $tenant = Tenants::query()->create([
            'name' => 'Acme Delete',
            'slug' => 'acme-delete-tenant',
            'owner_id' => $owner->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::Owner->value,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/v1/tenants/{$tenant->id}", [
            'password' => 'password',
        ]);

        $response->assertNoContent();
        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
    }
}
