<?php

namespace Modules\Task\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Task\Classes\DTOs\CreateTaskData;
use Modules\Task\Classes\DTOs\UpdateTaskData;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Events\TaskCompleted;
use Modules\Task\Events\TaskCreated;
use Modules\Task\Events\TaskUpdated;
use Modules\Task\Interfaces\Contracts\TaskRepositoryInterface;
use Modules\Task\Interfaces\Contracts\TaskServiceInterface;
use Modules\Task\Models\Task;
use Modules\User\Models\User;

class TaskService implements TaskServiceInterface
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    public function listForProject(int $projectId): Collection
    {
        return $this->taskRepository->listForProject($projectId);
    }

    public function create(CreateTaskData $data, User $actor): Task
    {
        $task = $this->taskRepository->create($data);

        TaskCreated::dispatch($task, $actor);

        return $task;
    }

    public function update(Task $task, UpdateTaskData $data, User $actor): Task
    {
        $wasCompleted = $task->status === TaskStatus::Done;

        $task = $this->taskRepository->update($task, $data);

        $isNowCompleted = $task->status === TaskStatus::Done;

        if ($isNowCompleted && ! $wasCompleted) {
            TaskCompleted::dispatch($task, $actor);
        } else {
            TaskUpdated::dispatch($task, $actor);
        }

        return $task;
    }

    public function delete(Task $task): void
    {
        $this->taskRepository->delete($task);
    }
}
