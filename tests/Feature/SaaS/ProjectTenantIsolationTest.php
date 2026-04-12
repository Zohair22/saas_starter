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

    public function test_admin_can_create_project(): void
    {
        [$tenant, $admin] = $this->createTenantWithMember(MembershipRole::Admin);

        Sanctum::actingAs($admin);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/projects', [
                'name' => 'Launch plan',
                'description' => 'Initial delivery track',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Launch plan');
        $this->assertDatabaseHas('projects', [
            'tenant_id' => $tenant->id,
            'name' => 'Launch plan',
        ]);
    }

    public function test_project_name_must_be_unique_per_tenant(): void
    {
        [$tenant, $admin] = $this->createTenantWithMember(MembershipRole::Admin);

        Project::query()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $admin->id,
            'name' => 'Roadmap',
            'description' => 'Original',
        ]);

        Sanctum::actingAs($admin);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/projects', [
                'name' => 'Roadmap',
                'description' => 'Duplicate',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_member_cannot_update_or_delete_project(): void
    {
        [$tenant, $owner] = $this->createTenantWithMember(MembershipRole::Owner);
        $member = User::factory()->create();

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $member->id,
            'role' => MembershipRole::Member->value,
        ]);

        $project = Project::query()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $owner->id,
            'name' => 'Restricted project',
            'description' => 'Owner managed',
        ]);

        Sanctum::actingAs($member);

        $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->patchJson('/api/v1/projects/'.$project->id, [
                'name' => 'Updated by member',
            ])
            ->assertForbidden();

        $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->deleteJson('/api/v1/projects/'.$project->id)
            ->assertForbidden();
    }

    public function test_update_rejects_empty_name_when_name_is_provided(): void
    {
        [$tenant, $admin] = $this->createTenantWithMember(MembershipRole::Admin);

        $project = Project::query()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $admin->id,
            'name' => 'Existing Name',
            'description' => 'Description',
        ]);

        Sanctum::actingAs($admin);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->patchJson('/api/v1/projects/'.$project->id, [
                'name' => '',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

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

    public function test_list_supports_query_filter_and_sort(): void
    {
        [$tenant, $admin] = $this->createTenantWithMember(MembershipRole::Admin);

        Project::query()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $admin->id,
            'name' => 'Zeta Platform',
            'description' => 'Infrastructure work',
        ]);

        Project::query()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $admin->id,
            'name' => 'Alpha Website',
            'description' => 'Marketing website',
        ]);

        Sanctum::actingAs($admin);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/v1/projects?q=website&sort=name_asc');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Alpha Website');
    }

    public function test_list_supports_pagination_meta(): void
    {
        [$tenant, $admin] = $this->createTenantWithMember(MembershipRole::Admin);

        Project::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/v1/projects?per_page=2&page=1');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.total', 3);
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
