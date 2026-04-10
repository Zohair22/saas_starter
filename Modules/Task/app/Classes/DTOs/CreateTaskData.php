<?php

namespace Modules\Task\Classes\DTOs;

use Modules\Task\Enums\TaskPriority;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Http\Requests\StoreTaskRequest;

class CreateTaskData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $projectId,
        public readonly int $createdBy,
        public readonly ?int $assignedTo,
        public readonly string $title,
        public readonly ?string $description,
        public readonly TaskStatus $status,
        public readonly TaskPriority $priority,
        public readonly ?string $dueAt,
    ) {}

    public static function fromRequest(StoreTaskRequest $request): self
    {
        return new self(
            tenantId: (int) data_get($request->attributes->get('tenant'), 'id'),
            projectId: (int) $request->route('project')->id,
            createdBy: (int) $request->user()->id,
            assignedTo: $request->validated('assigned_to') ? (int) $request->validated('assigned_to') : null,
            title: $request->validated('title'),
            description: $request->validated('description'),
            status: TaskStatus::from($request->validated('status', TaskStatus::Open->value)),
            priority: TaskPriority::from($request->validated('priority', TaskPriority::Medium->value)),
            dueAt: $request->validated('due_at'),
        );
    }
}
