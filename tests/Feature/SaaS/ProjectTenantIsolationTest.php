<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Project\Models\Project;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class ProjectTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_only_returns_projects_for_current_tenant(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $tenantA = Tenants::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'owner_id' => $userA->id,
        ]);

        $tenantB = Tenants::query()->create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'owner_id' => $userB->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenantA->id,
            'user_id' => $userA->id,
            'role' => MembershipRole::Admin->value,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenantB->id,
            'user_id' => $userB->id,
            'role' => MembershipRole::Admin->value,
        ]);

        Project::query()->create([
            'tenant_id' => $tenantA->id,
            'created_by' => $userA->id,
            'name' => 'Project A1',
            'description' => 'A',
        ]);

        Project::query()->create([
            'tenant_id' => $tenantB->id,
            'created_by' => $userB->id,
            'name' => 'Project B1',
            'description' => 'B',
        ]);

        Sanctum::actingAs($userA);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenantA->id)
            ->getJson('/api/v1/projects');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'name' => 'Project A1',
        ]);
        $response->assertJsonMissing([
            'name' => 'Project B1',
        ]);
    }

    public function test_cannot_access_project_from_another_tenant(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $tenantA = Tenants::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'owner_id' => $userA->id,
        ]);

        $tenantB = Tenants::query()->create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'owner_id' => $userB->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenantA->id,
            'user_id' => $userA->id,
            'role' => MembershipRole::Admin->value,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenantB->id,
            'user_id' => $userB->id,
            'role' => MembershipRole::Admin->value,
        ]);

        $projectB = Project::query()->create([
            'tenant_id' => $tenantB->id,
            'created_by' => $userB->id,
            'name' => 'Project B1',
            'description' => 'B',
        ]);

        Sanctum::actingAs($userA);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenantA->id)
            ->getJson('/api/v1/projects/'.$projectB->id);

        $response->assertNotFound();
    }
}
