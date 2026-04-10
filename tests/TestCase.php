<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return array{0: Tenants, 1: User}
     */
    protected function createTenantWithMember(MembershipRole $role = MembershipRole::Member): array
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create([
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(2),
            'owner_id' => $user->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => $role->value,
        ]);

        return [$tenant, $user];
    }
}
