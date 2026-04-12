<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Billing\Database\Seeders\BillingDatabaseSeeder;
// use Modules\Membership\Enums\MembershipRole;
// use Modules\Membership\Models\Membership;
// use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed billing plans first so new tenants can reference plans.
        $this->call([
            BillingDatabaseSeeder::class,
        ]);

        //     $owner = User::query()->firstOrCreate(
        //         ['email' => 'test@example.com'],
        //         [
        //             'name' => 'Test User',
        //             'password' => 'password',
        //         ]
        //     );

        //     $tenant = Tenants::query()->firstOrCreate(
        //         ['slug' => 'demo'],
        //         [
        //             'name' => 'Demo Tenant',
        //             'owner_id' => $owner->id,
        //         ]
        //     );

        //     Membership::query()->firstOrCreate(
        //         [
        //             'tenant_id' => $tenant->id,
        //             'user_id' => $owner->id,
        //         ],
        //         [
        //             'role' => MembershipRole::Owner->value,
        //         ]
        //     );
    }
}
