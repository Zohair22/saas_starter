<?php

namespace Modules\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Task\Enums\TaskPriority;
use Modules\Task\Enums\TaskStatus;

class StoreTaskRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'due_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
