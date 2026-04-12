<?php

namespace Modules\Task\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Task\Classes\DTOs\CreateTaskData;
use Modules\Task\Classes\DTOs\UpdateTaskData;
use Modules\Task\Interfaces\Contracts\TaskRepositoryInterface;
use Modules\Task\Models\Task;

class TaskRepository implements TaskRepositoryInterface
{
    public function listForProject(int $projectId, array $filters = []): Collection|LengthAwarePaginator
    {
        $query = Task::query()
            ->where('project_id', $projectId)
            ->with(['creator:id,name,email', 'assignee:id,name,email'])
            ->where('tenant_id', (int) data_get(request()->attributes->get('tenant'), 'id'));

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', (string) $filters['priority']);
        }

        match ((string) ($filters['sort'] ?? 'updated_desc')) {
            'updated_asc' => $query->orderBy('updated_at'),
            'due_asc' => $query->orderBy('due_at'),
            'due_desc' => $query->orderByDesc('due_at'),
            default => $query->orderByDesc('updated_at'),
        };

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : null;

        if ($perPage !== null && $perPage > 0) {
            $page = max((int) ($filters['page'] ?? 1), 1);

            return $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();
        }

        return $query->get();
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
