<?php

namespace Modules\Task\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Task\Models\Task;
use Modules\User\Models\User;

class TaskUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly User $actor,
    ) {}

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('tenant.'.$this->task->tenant_id.'.project.'.$this->task->project_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'task' => [
                'id' => $this->task->id,
                'project_id' => $this->task->project_id,
                'title' => $this->task->title,
                'description' => $this->task->description,
                'status' => $this->task->status->value,
                'priority' => $this->task->priority->value,
                'assigned_to' => $this->task->assigned_to,
                'due_at' => $this->task->due_at?->toIso8601String(),
                'updated_at' => $this->task->updated_at?->toIso8601String(),
            ],
            'actor' => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
            ],
        ];
    }
}
