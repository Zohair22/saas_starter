<?php

namespace Modules\Tenant\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tenant\Models\Tenants;

class TenantsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Tenants::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}
