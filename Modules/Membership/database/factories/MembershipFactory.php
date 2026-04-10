<?php

namespace Modules\Membership\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Membership\Models\Membership;

class MembershipFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Membership::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}
