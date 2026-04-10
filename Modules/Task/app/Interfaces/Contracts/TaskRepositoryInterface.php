<?php

namespace Modules\Task\Interfaces\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Task\Classes\DTOs\CreateTaskData;
use Modules\Task\Classes\DTOs\UpdateTaskData;
use Modules\Task\Models\Task;

interface TaskRepositoryInterface
{
    public function listForProject(int $projectId): Collection;

    public function create(CreateTaskData $data): Task;

    public function update(Task $task, UpdateTaskData $data): Task;

    public function delete(Task $task): void;
}
