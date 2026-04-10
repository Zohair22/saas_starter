<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Billing\Models\Plan;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Project\Models\Project;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class BillingAuthorizationConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_mutate_subscription(): void
    {
        [$member, $tenant] = $this->createTenantActor(MembershipRole::Member->value);

        Plan::query()->create([
            'code' => 'pro',
            'name' => 'Pro',
            'stripe_price_id' => 'price_pro_test',
            'max_users' => 10,
            'max_projects' => 10,
            'api_rate_limit' => 1000,
            'is_active' => true,
        ]);

        Sanctum::actingAs($member);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/billing/subscribe', [
                'plan_code' => 'pro',
                'payment_method' => 'pm_card_visa',
            ]);

        $response->assertForbidden();
    }

    public function test_inactive_plan_code_is_rejected_by_validation(): void
    {
        [$owner, $tenant] = $this->createTenantActor(MembershipRole::Owner->value);

        Plan::query()->create([
            'code' => 'legacy',
            'name' => 'Legacy',
            'stripe_price_id' => 'price_legacy_test',
            'max_users' => 10,
            'max_projects' => 10,
            'api_rate_limit' => 1000,
            'is_active' => false,
        ]);

        Sanctum::actingAs($owner);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->patchJson('/api/v1/billing/subscription', [
                'plan_code' => 'legacy',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['plan_code']);
    }

    public function test_cross_tenant_billing_access_is_forbidden(): void
    {
        [$owner] = $this->createTenantActor(MembershipRole::Owner->value);

        $otherTenant = Tenants::query()->create([
            'name' => 'Other',
            'slug' => 'other-'.str()->random(5),
            'owner_id' => User::factory()->create()->id,
        ]);

        Sanctum::actingAs($owner);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $otherTenant->id)
            ->getJson('/api/v1/billing/plans');

        $response->assertForbidden();
    }

    public function test_downgrade_is_rejected_when_current_usage_exceeds_target_plan_limits(): void
    {
        [$owner, $tenant] = $this->createTenantActor(MembershipRole::Owner->value);
        $projectOwner = User::factory()->create();

        Project::query()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $projectOwner->id,
            'name' => 'Project 1',
            'description' => 'One',
        ]);
        Project::query()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $projectOwner->id,
            'name' => 'Project 2',
            'description' => 'Two',
        ]);

        Plan::query()->create([
            'code' => 'strict',
            'name' => 'Strict',
            'stripe_price_id' => 'price_strict_test',
            'max_users' => 1,
            'max_projects' => 1,
            'api_rate_limit' => 100,
            'is_active' => true,
        ]);

        Sanctum::actingAs($owner);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->patchJson('/api/v1/billing/subscription', [
                'plan_code' => 'strict',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['plan_code']);
    }

    /**
     * @return array{0: User, 1: Tenants}
     */
    private function createTenantActor(string $role): array
    {
        $actor = User::factory()->create();

        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme-'.str()->random(5),
            'owner_id' => $actor->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $actor->id,
            'role' => $role,
        ]);

        return [$actor, $tenant];
    }
}
