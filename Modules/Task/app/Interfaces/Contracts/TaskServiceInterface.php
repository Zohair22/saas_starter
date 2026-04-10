<?php

namespace Modules\Task\Interfaces\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Task\Classes\DTOs\CreateTaskData;
use Modules\Task\Classes\DTOs\UpdateTaskData;
use Modules\Task\Models\Task;
use Modules\User\Models\User;

interface TaskServiceInterface
{
    public function listForProject(int $projectId): Collection;

    public function create(CreateTaskData $data, User $actor): Task;

    public function update(Task $task, UpdateTaskData $data, User $actor): Task;

    public function delete(Task $task): void;
}
