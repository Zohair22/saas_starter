<?php

namespace Tests\Feature\SaaS;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Project\Models\Project;
use Modules\Task\Enums\TaskPriority;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Events\TaskCompleted;
use Modules\Task\Events\TaskCreated;
use Modules\Task\Events\TaskUpdated;
use Modules\Task\Models\Task;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class TaskRealtimeBroadcastingTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_created_event_broadcasts_to_project_presence_channel(): void
    {
        [$task, $actor] = $this->createTaskWithActor();

        $event = new TaskCreated($task, $actor);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PresenceChannel::class, $channels[0]);
        $this->assertSame('presence-tenant.'.$task->tenant_id.'.project.'.$task->project_id, $channels[0]->name);
        $this->assertSame('task.created', $event->broadcastAs());

        $payload = $event->broadcastWith();

        $this->assertSame($task->id, $payload['task']['id']);
        $this->assertSame($actor->id, $payload['actor']['id']);
    }

    public function test_task_completed_and_updated_events_broadcast_with_expected_names(): void
    {
        [$task, $actor] = $this->createTaskWithActor();

        $updatedEvent = new TaskUpdated($task, $actor);
        $completedEvent = new TaskCompleted($task, $actor);

        $this->assertSame('task.updated', $updatedEvent->broadcastAs());
        $this->assertSame('task.completed', $completedEvent->broadcastAs());
        $this->assertSame($task->id, $updatedEvent->broadcastWith()['task']['id']);
        $this->assertSame($task->id, $completedEvent->broadcastWith()['task']['id']);
    }

    /**
     * @return array{Task, User}
     */
    private function createTaskWithActor(): array
    {
        $owner = User::factory()->create();

        $tenant = Tenants::query()->create([
            'name' => 'Realtime Tenant',
            'slug' => 'realtime-tenant',
            'owner_id' => $owner->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::Owner,
        ]);

        $project = Project::query()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $owner->id,
            'name' => 'Realtime Project',
        ]);

        $task = Task::query()->create([
            'tenant_id' => $tenant->id,
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'title' => 'Broadcast me',
            'status' => TaskStatus::Open,
            'priority' => TaskPriority::Medium,
        ]);

        return [$task, $owner];
    }
}
