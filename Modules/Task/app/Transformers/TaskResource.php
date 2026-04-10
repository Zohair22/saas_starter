<?php

namespace Modules\Task\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'created_by' => $this->created_by,
            'assigned_to' => $this->assigned_to,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_at' => $this->due_at,
            'creator' => $this->whenLoaded('creator'),
            'assignee' => $this->whenLoaded('assignee'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
