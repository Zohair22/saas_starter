<?php

namespace Modules\Project\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Project\Models\Project;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;

class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenants::factory(),
            'created_by' => User::factory(),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
