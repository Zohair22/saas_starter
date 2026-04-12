<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class TenantLifecycleEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_tenant_blocks_mutating_project_requests(): void
    {
        [$user, $tenant] = $this->createTenantMemberWithStatus('downgraded', null);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/projects', [
                'name' => 'Blocked Project',
            ]);

        $response->assertStatus(423)
            ->assertJsonPath('lifecycle.billing_status', 'downgraded')
            ->assertJsonPath('lifecycle.is_write_locked', true);
    }

    public function test_locked_tenant_can_still_read_project_listing(): void
    {
        [$user, $tenant] = $this->createTenantMemberWithStatus('downgraded', null);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/v1/projects');

        $response->assertOk();
    }

    public function test_tenant_in_grace_period_can_still_mutate_projects(): void
    {
        [$user, $tenant] = $this->createTenantMemberWithStatus('canceled', now()->addDay());

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/projects', [
                'name' => 'Grace Project',
            ]);

        $response->assertCreated();
    }

    private function createTenantMemberWithStatus(string $status, $gracePeriodEndsAt): array
    {
        $user = User::factory()->create();

        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme-'.str()->random(8),
            'owner_id' => $user->id,
            'billing_status' => $status,
            'grace_period_ends_at' => $gracePeriodEndsAt,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Owner->value,
        ]);

        return [$user, $tenant];
    }
}
