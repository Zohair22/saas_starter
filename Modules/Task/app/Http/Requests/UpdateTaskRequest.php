<?php

namespace Modules\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Task\Enums\TaskPriority;
use Modules\Task\Enums\TaskStatus;

class UpdateTaskRequest extends FormRequest
{
    public function rules(): array
    {
        $tenantId = (int) data_get($this->attributes->get('tenant'), 'id');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('memberships', 'user_id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'due_at' => ['nullable', 'date'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
