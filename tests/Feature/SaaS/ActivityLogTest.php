<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ActivityLog\Enums\ActivityAction;
use Modules\ActivityLog\Models\ActivityLog;
use Modules\Membership\Enums\MembershipRole;
use Modules\Project\Models\Project;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Models\Task;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_project_writes_activity_log(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Admin);

        $this->actingAs($user)
            ->withHeaders(['X-Tenant-ID' => $tenant->id])
            ->postJson('/api/v1/projects', ['name' => 'My Project'])
            ->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => $user->id,
            'action' => ActivityAction::ProjectCreated->value,
        ]);
    }

    public function test_creating_task_writes_activity_log(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Admin);
        $project = Project::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $user->id]);

        $this->actingAs($user)
            ->withHeaders(['X-Tenant-ID' => $tenant->id])
            ->postJson("/api/v1/projects/{$project->id}/tasks", ['title' => 'My Task'])
            ->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => $user->id,
            'action' => ActivityAction::TaskCreated->value,
        ]);
    }

    public function test_completing_task_writes_task_completed_log(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Admin);
        $project = Project::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $user->id]);
        $task = Task::factory()->open()->create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'created_by' => $user->id]);

        $this->actingAs($user)
            ->withHeaders(['X-Tenant-ID' => $tenant->id])
            ->patchJson("/api/v1/projects/{$project->id}/tasks/{$task->id}", [
                'status' => TaskStatus::Done->value,
            ])
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id,
            'action' => ActivityAction::TaskCompleted->value,
        ]);
    }

    public function test_any_member_can_view_activity_logs(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Member);

        app()->instance('tenant', $tenant);

        ActivityLog::query()->create([
            'tenant_id' => $tenant->id,
            'actor_id' => $user->id,
            'action' => ActivityAction::ProjectCreated,
            'subject_type' => null,
            'subject_id' => null,
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Tenant-ID' => $tenant->id])
            ->getJson('/api/v1/activity-logs');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_activity_logs_are_scoped_to_tenant(): void
    {
        [$tenantA, $userA] = $this->createTenantWithMember(MembershipRole::Admin);
        [$tenantB, $userB] = $this->createTenantWithMember(MembershipRole::Admin);

        // Create a project (and log) for tenant A
        $this->actingAs($userA)
            ->withHeaders(['X-Tenant-ID' => $tenantA->id])
            ->postJson('/api/v1/projects', ['name' => 'Tenant A Project'])
            ->assertCreated();

        // Tenant B should see zero logs
        $response = $this->actingAs($userB)
            ->withHeaders(['X-Tenant-ID' => $tenantB->id])
            ->getJson('/api/v1/activity-logs');

        $response->assertOk()->assertJsonCount(0, 'data');
    }
}
