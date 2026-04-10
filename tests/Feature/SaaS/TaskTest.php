<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Membership\Enums\MembershipRole;
use Modules\Project\Models\Project;
use Modules\Task\Enums\TaskPriority;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Events\TaskCompleted;
use Modules\Task\Events\TaskCreated;
use Modules\Task\Events\TaskUpdated;
use Modules\Task\Models\Task;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_list_tasks_for_project(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Member);
        $project = Project::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $user->id]);
        Task::factory()->count(3)->create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Tenant-ID' => $tenant->id])
            ->getJson("/api/v1/projects/{$project->id}/tasks");

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_task_and_event_is_dispatched(): void
    {
        Event::fake([TaskCreated::class]);

        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Admin);
        $project = Project::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Tenant-ID' => $tenant->id])
            ->postJson("/api/v1/projects/{$project->id}/tasks", [
                'title' => 'Build feature X',
                'priority' => TaskPriority::High->value,
            ]);

        $response->assertCreated()->assertJsonPath('data.title', 'Build feature X');

        Event::assertDispatched(TaskCreated::class);
    }

    public function test_member_cannot_create_task(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Member);
        $project = Project::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Tenant-ID' => $tenant->id])
            ->postJson("/api/v1/projects/{$project->id}/tasks", ['title' => 'Task']);

        $response->assertForbidden();
    }

    public function test_updating_task_to_done_dispatches_task_completed(): void
    {
        Event::fake([TaskCompleted::class, TaskUpdated::class]);

        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Admin);
        $project = Project::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $user->id]);
        $task = Task::factory()->open()->create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'created_by' => $user->id]);

        $this->actingAs($user)
            ->withHeaders(['X-Tenant-ID' => $tenant->id])
            ->patchJson("/api/v1/projects/{$project->id}/tasks/{$task->id}", [
                'status' => TaskStatus::Done->value,
            ])
            ->assertOk();

        Event::assertDispatched(TaskCompleted::class);
        Event::assertNotDispatched(TaskUpdated::class);
    }

    public function test_updating_task_without_completion_dispatches_task_updated(): void
    {
        Event::fake([TaskCompleted::class, TaskUpdated::class]);

        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Admin);
        $project = Project::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $user->id]);
        $task = Task::factory()->open()->create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'created_by' => $user->id]);

        $this->actingAs($user)
            ->withHeaders(['X-Tenant-ID' => $tenant->id])
            ->patchJson("/api/v1/projects/{$project->id}/tasks/{$task->id}", [
                'title' => 'Updated title',
            ])
            ->assertOk();

        Event::assertDispatched(TaskUpdated::class);
        Event::assertNotDispatched(TaskCompleted::class);
    }

    public function test_admin_can_delete_task(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Admin);
        $project = Project::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $user->id]);
        $task = Task::factory()->create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'created_by' => $user->id]);

        $this->actingAs($user)
            ->withHeaders(['X-Tenant-ID' => $tenant->id])
            ->deleteJson("/api/v1/projects/{$project->id}/tasks/{$task->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_tasks_are_scoped_to_tenant(): void
    {
        [$tenantA, $userA] = $this->createTenantWithMember(MembershipRole::Member);
        [$tenantB, $userB] = $this->createTenantWithMember(MembershipRole::Member);

        $projectA = Project::factory()->create(['tenant_id' => $tenantA->id, 'created_by' => $userA->id]);
        $projectB = Project::factory()->create(['tenant_id' => $tenantB->id, 'created_by' => $userB->id]);

        Task::factory()->create(['tenant_id' => $tenantA->id, 'project_id' => $projectA->id, 'created_by' => $userA->id]);
        Task::factory()->create(['tenant_id' => $tenantB->id, 'project_id' => $projectB->id, 'created_by' => $userB->id]);

        // Tenant A cannot see Tenant B's project tasks
        $this->actingAs($userA)
            ->withHeaders(['X-Tenant-ID' => $tenantA->id])
            ->getJson("/api/v1/projects/{$projectB->id}/tasks")
            ->assertNotFound();
    }
}
