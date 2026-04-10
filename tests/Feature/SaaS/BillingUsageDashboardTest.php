<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Billing\Models\Plan;
use Modules\Membership\Enums\MembershipRole;
use Modules\Project\Models\Project;
use Tests\TestCase;

class BillingUsageDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_usage_dashboard(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Admin);

        $plan = Plan::query()->create([
            'code' => 'pro',
            'name' => 'Pro',
            'stripe_price_id' => 'price_pro_test',
            'max_users' => 5,
            'max_projects' => 10,
            'api_rate_limit' => 100,
            'is_active' => true,
        ]);

        $tenant->update(['plan_id' => $plan->id]);

        Project::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Tenant-ID' => (string) $tenant->id])
            ->getJson('/api/v1/billing/usage');

        $response->assertOk();
        $response->assertJsonPath('limits.max_users', 5);
        $response->assertJsonPath('limits.max_projects', 10);
        $response->assertJsonPath('plan.code', 'pro');
        $response->assertJsonStructure([
            'plan',
            'limits',
            'usage',
            'utilization',
            'history',
        ]);
    }
}
