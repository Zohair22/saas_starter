<?php

namespace Modules\Task\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Task\Classes\DTOs\CreateTaskData;
use Modules\Task\Classes\DTOs\UpdateTaskData;
use Modules\Task\Interfaces\Contracts\TaskRepositoryInterface;
use Modules\Task\Models\Task;

class TaskRepository implements TaskRepositoryInterface
{
    public function listForProject(int $projectId): Collection
    {
        return Task::query()
            ->where('project_id', $projectId)
            ->with(['creator:id,name,email', 'assignee:id,name,email'])
            ->latest()
            ->get();
    }

    public function create(CreateTaskData $data): Task
    {
        return Task::create([
            'project_id' => $data->projectId,
            'created_by' => $data->createdBy,
            'assigned_to' => $data->assignedTo,
            'title' => $data->title,
            'description' => $data->description,
            'status' => $data->status,
            'priority' => $data->priority,
            'due_at' => $data->dueAt,
        ])->load(['creator:id,name,email', 'assignee:id,name,email']);
    }

    public function update(Task $task, UpdateTaskData $data): Task
    {
        $attributes = array_filter([
            'title' => $data->title,
            'description' => $data->description,
            'status' => $data->status,
            'priority' => $data->priority,
        ], fn ($value) => $value !== null);

        if ($data->clearAssignee) {
            $attributes['assigned_to'] = null;
        } elseif ($data->assignedTo !== null) {
            $attributes['assigned_to'] = $data->assignedTo;
        }

        if ($data->clearDueAt) {
            $attributes['due_at'] = null;
        } elseif ($data->dueAt !== null) {
            $attributes['due_at'] = $data->dueAt;
        }

        $task->update($attributes);

        return $task->refresh()->load(['creator:id,name,email', 'assignee:id,name,email']);
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }
}
