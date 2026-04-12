<?php

namespace Tests\Unit\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Task\Classes\DTOs\CreateTaskData;
use Modules\Task\Classes\DTOs\UpdateTaskData;
use Modules\Task\Enums\TaskPriority;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Events\TaskCompleted;
use Modules\Task\Events\TaskCreated;
use Modules\Task\Events\TaskUpdated;
use Modules\Task\Interfaces\Contracts\TaskRepositoryInterface;
use Modules\Task\Models\Task;
use Modules\Task\Services\TaskService;
use Modules\User\Models\User;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    private TaskRepositoryInterface $repository;

    private TaskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(TaskRepositoryInterface::class);
        $this->service = new TaskService($this->repository);
    }

    public function test_list_for_project_delegates_to_repository(): void
    {
        $collection = new Collection;
        $filters = ['status' => 'open', 'sort' => 'updated_desc', 'per_page' => 10, 'page' => 1];
        $this->repository->shouldReceive('listForProject')->with(42, $filters)->andReturn($collection);

        $result = $this->service->listForProject(42, $filters);

        $this->assertSame($collection, $result);
    }

    public function test_create_calls_repository_and_dispatches_task_created_event(): void
    {
        Event::fake([TaskCreated::class]);

        $task = Mockery::mock(Task::class);
        $actor = Mockery::mock(User::class);
        $data = new CreateTaskData(
            tenantId: 1,
            projectId: 2,
            createdBy: 3,
            assignedTo: null,
            title: 'Do something',
            description: null,
            status: TaskStatus::Open,
            priority: TaskPriority::Medium,
            dueAt: null,
        );

        $this->repository->shouldReceive('create')->with($data)->andReturn($task);

        $result = $this->service->create($data, $actor);

        $this->assertSame($task, $result);
        Event::assertDispatched(TaskCreated::class, function (TaskCreated $event) use ($task, $actor): bool {
            return $event->task === $task && $event->actor === $actor;
        });
    }

    public function test_update_dispatches_task_updated_when_status_does_not_transition_to_done(): void
    {
        Event::fake([TaskUpdated::class, TaskCompleted::class]);

        $original = new Task(['status' => TaskStatus::Open, 'priority' => TaskPriority::Medium]);
        $updated = new Task(['status' => TaskStatus::InProgress, 'priority' => TaskPriority::Medium]);
        $actor = Mockery::mock(User::class);
        $data = new UpdateTaskData(null, null, null, TaskStatus::InProgress, null, null, false, false);

        $this->repository->shouldReceive('update')->once()->andReturn($updated);

        $this->service->update($original, $data, $actor);

        Event::assertDispatched(TaskUpdated::class);
        Event::assertNotDispatched(TaskCompleted::class);
    }

    public function test_update_dispatches_task_completed_when_status_transitions_to_done(): void
    {
        Event::fake([TaskUpdated::class, TaskCompleted::class]);

        $original = new Task(['status' => TaskStatus::Open, 'priority' => TaskPriority::Medium]);
        $updated = new Task(['status' => TaskStatus::Done, 'priority' => TaskPriority::Medium]);
        $actor = Mockery::mock(User::class);
        $data = new UpdateTaskData(null, null, null, TaskStatus::Done, null, null, false, false);

        $this->repository->shouldReceive('update')->once()->andReturn($updated);

        $this->service->update($original, $data, $actor);

        Event::assertDispatched(TaskCompleted::class, function (TaskCompleted $event) use ($updated, $actor): bool {
            return $event->task === $updated && $event->actor === $actor;
        });
        Event::assertNotDispatched(TaskUpdated::class);
    }

    public function test_update_dispatches_task_updated_not_completed_when_already_done(): void
    {
        Event::fake([TaskUpdated::class, TaskCompleted::class]);

        $original = new Task(['status' => TaskStatus::Done, 'priority' => TaskPriority::Medium]);
        $updated = new Task(['status' => TaskStatus::Done, 'priority' => TaskPriority::High]);
        $actor = Mockery::mock(User::class);
        $data = new UpdateTaskData(null, null, null, null, TaskPriority::High, null, false, false);

        $this->repository->shouldReceive('update')->once()->andReturn($updated);

        $this->service->update($original, $data, $actor);

        Event::assertDispatched(TaskUpdated::class);
        Event::assertNotDispatched(TaskCompleted::class);
    }

    public function test_delete_delegates_to_repository(): void
    {
        $task = Mockery::mock(Task::class);
        $this->repository->shouldReceive('delete')->with($task)->once();

        $this->service->delete($task);
    }
}
