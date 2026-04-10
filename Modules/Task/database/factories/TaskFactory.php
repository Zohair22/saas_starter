<?php

namespace Modules\Task\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Task\Enums\TaskPriority;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Models\Task;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'status' => TaskStatus::Open,
            'priority' => TaskPriority::Medium,
            'due_at' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => TaskStatus::Open]);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => TaskStatus::InProgress]);
    }

    public function done(): static
    {
        return $this->state(['status' => TaskStatus::Done]);
    }
}
