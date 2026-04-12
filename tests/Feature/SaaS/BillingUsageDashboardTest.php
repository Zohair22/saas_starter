<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Billing\Models\Plan;
use Modules\Billing\Models\TenantUsageCounter;
use Modules\Membership\Enums\MembershipRole;
use Modules\Project\Models\Project;
use Tests\TestCase;

class BillingUsageDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_usage_dashboard(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Member);

        $plan = Plan::query()->create([
            'code' => 'starter',
            'name' => 'Starter',
            'stripe_price_id' => 'price_starter_test',
            'max_users' => 3,
            'max_projects' => 5,
            'api_rate_limit' => 100,
            'is_active' => true,
        ]);

        $tenant->update(['plan_id' => $plan->id]);

        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Tenant-ID' => (string) $tenant->id])
            ->getJson('/api/v1/billing/usage');

        $response->assertOk();
        $response->assertJsonPath('plan.code', 'starter');
    }

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
            'history_months',
            'history',
        ]);
    }

    public function test_usage_history_is_scoped_and_honors_months_limit(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Admin);
        [$otherTenant] = $this->createTenantWithMember(MembershipRole::Admin);

        TenantUsageCounter::query()->create([
            'tenant_id' => $tenant->id,
            'period_start' => now()->startOfMonth(),
            'users_count' => 2,
            'projects_count' => 4,
            'api_requests_count' => 10,
        ]);

        TenantUsageCounter::query()->create([
            'tenant_id' => $tenant->id,
            'period_start' => now()->subMonth()->startOfMonth(),
            'users_count' => 3,
            'projects_count' => 5,
            'api_requests_count' => 20,
        ]);

        TenantUsageCounter::query()->create([
            'tenant_id' => $otherTenant->id,
            'period_start' => now()->startOfMonth(),
            'users_count' => 99,
            'projects_count' => 99,
            'api_requests_count' => 99,
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Tenant-ID' => (string) $tenant->id])
            ->getJson('/api/v1/billing/usage?months=1');

        $response->assertOk();
        $response->assertJsonPath('history_months', 1);
        $response->assertJsonCount(1, 'history');
        $response->assertJsonMissing([
            'users_count' => 99,
            'projects_count' => 99,
            'api_requests_count' => 99,
        ]);
    }
}
