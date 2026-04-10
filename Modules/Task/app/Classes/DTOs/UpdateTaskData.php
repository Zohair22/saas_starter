<?php

namespace Modules\Task\Classes\DTOs;

use Modules\Task\Enums\TaskPriority;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Http\Requests\UpdateTaskRequest;

class UpdateTaskData
{
    public function __construct(
        public readonly ?int $assignedTo,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?TaskStatus $status,
        public readonly ?TaskPriority $priority,
        public readonly ?string $dueAt,
        public readonly bool $clearAssignee,
        public readonly bool $clearDueAt,
    ) {}

    public static function fromRequest(UpdateTaskRequest $request): self
    {
        return new self(
            assignedTo: $request->validated('assigned_to') ? (int) $request->validated('assigned_to') : null,
            title: $request->validated('title'),
            description: $request->validated('description'),
            status: $request->has('status') ? TaskStatus::from($request->validated('status')) : null,
            priority: $request->has('priority') ? TaskPriority::from($request->validated('priority')) : null,
            dueAt: $request->validated('due_at'),
            clearAssignee: $request->validated('assigned_to') === null && $request->has('assigned_to'),
            clearDueAt: $request->validated('due_at') === null && $request->has('due_at'),
        );
    }
}
