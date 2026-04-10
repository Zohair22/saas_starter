<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Billing\Models\Plan;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Project\Models\Project;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class UsageCounterTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_counters_track_membership_and_project_changes(): void
    {
        $owner = User::factory()->create();
        $newMember = User::factory()->create();

        $plan = Plan::query()->create([
            'code' => 'pro',
            'name' => 'Pro',
            'stripe_price_id' => 'price_pro_test',
            'max_users' => 50,
            'max_projects' => 50,
            'api_rate_limit' => 5000,
            'is_active' => true,
        ]);

        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme-'.str()->random(5),
            'owner_id' => $owner->id,
            'plan_id' => $plan->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::Owner->value,
        ]);

        Sanctum::actingAs($owner);

        $this->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/memberships', [
                'user_id' => $newMember->id,
                'role' => MembershipRole::Member->value,
            ])->assertCreated();

        $this->assertDatabaseHas('tenant_usage_counters', [
            'tenant_id' => $tenant->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'users_count' => 2,
        ]);

        $createProjectResponse = $this->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/projects', [
                'name' => 'Usage Test Project',
                'description' => 'usage',
            ]);

        $createProjectResponse->assertCreated();
        $projectId = (int) $createProjectResponse->json('data.id');

        $this->assertDatabaseHas('tenant_usage_counters', [
            'tenant_id' => $tenant->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'projects_count' => 1,
        ]);

        $project = Project::query()->findOrFail($projectId);

        $this->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->deleteJson('/api/v1/projects/'.$project->id)
            ->assertNoContent();

        $this->assertDatabaseHas('tenant_usage_counters', [
            'tenant_id' => $tenant->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'projects_count' => 0,
        ]);
    }
}
