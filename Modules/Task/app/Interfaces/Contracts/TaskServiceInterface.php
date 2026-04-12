<?php

namespace Modules\Task\Interfaces\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Task\Classes\DTOs\CreateTaskData;
use Modules\Task\Classes\DTOs\UpdateTaskData;
use Modules\Task\Models\Task;
use Modules\User\Models\User;

interface TaskServiceInterface
{
    /**
     * @param  array{q?:string,status?:string,priority?:string,sort?:string,per_page?:int,page?:int}  $filters
     */
    public function listForProject(int $projectId, array $filters = []): Collection|LengthAwarePaginator;

    public function create(CreateTaskData $data, User $actor): Task;

    public function update(Task $task, UpdateTaskData $data, User $actor): Task;

    public function delete(Task $task): void;
}
