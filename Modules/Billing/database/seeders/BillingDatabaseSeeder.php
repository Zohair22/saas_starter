<?php

namespace Modules\Billing\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Billing\Models\Plan;

class BillingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $plans = config('billing.plans', []);

        foreach ($plans as $code => $plan) {
            Plan::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $plan['name'],
                    'stripe_price_id' => $plan['stripe_price_id'] ?? null,
                    'max_users' => $plan['max_users'],
                    'max_projects' => $plan['max_projects'],
                    'api_rate_limit' => $plan['api_rate_limit'],
                    'is_active' => true,
                ]
            );
        }
    }
}
