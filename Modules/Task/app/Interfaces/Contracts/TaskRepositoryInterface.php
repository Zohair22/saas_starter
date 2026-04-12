<?php

namespace Modules\Task\Interfaces\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Task\Classes\DTOs\CreateTaskData;
use Modules\Task\Classes\DTOs\UpdateTaskData;
use Modules\Task\Models\Task;

interface TaskRepositoryInterface
{
    /**
     * @param  array{q?:string,status?:string,priority?:string,sort?:string,per_page?:int,page?:int}  $filters
     */
    public function listForProject(int $projectId, array $filters = []): Collection|LengthAwarePaginator;

    public function create(CreateTaskData $data): Task;

    public function update(Task $task, UpdateTaskData $data): Task;

    public function delete(Task $task): void;
}
