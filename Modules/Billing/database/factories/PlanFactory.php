<?php

namespace Modules\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Models\Plan;

class PlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}
