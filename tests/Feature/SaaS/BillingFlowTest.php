<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Modules\Billing\Interfaces\Contracts\BillingServiceInterface;
use Modules\Billing\Models\Plan;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class BillingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_endpoint_works_with_mocked_service(): void
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'owner_id' => $user->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Owner->value,
        ]);

        Plan::query()->create([
            'code' => 'pro',
            'name' => 'Pro',
            'stripe_price_id' => 'price_pro_test',
            'max_users' => 10,
            'max_projects' => 10,
            'api_rate_limit' => 1000,
            'is_active' => true,
        ]);

        $service = Mockery::mock(BillingServiceInterface::class);
        $service->shouldReceive('subscribe')->once()->andReturn(null);
        $this->app->instance(BillingServiceInterface::class, $service);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/billing/subscribe', [
                'plan_code' => 'pro',
                'payment_method' => 'pm_card_visa',
            ]);

        $response->assertCreated();
        $response->assertJsonFragment(['message' => 'Subscription created.']);
    }

    public function test_swap_endpoint_works_with_mocked_service(): void
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'owner_id' => $user->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Owner->value,
        ]);

        Plan::query()->create([
            'code' => 'enterprise',
            'name' => 'Enterprise',
            'stripe_price_id' => 'price_enterprise_test',
            'max_users' => 100,
            'max_projects' => 100,
            'api_rate_limit' => 10000,
            'is_active' => true,
        ]);

        $service = Mockery::mock(BillingServiceInterface::class);
        $service->shouldReceive('swap')->once()->andReturn(new Subscription);
        $this->app->instance(BillingServiceInterface::class, $service);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->patchJson('/api/v1/billing/subscription', [
                'plan_code' => 'enterprise',
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['message' => 'Subscription swapped.']);
    }

    public function test_cancel_endpoint_works_with_mocked_service(): void
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'owner_id' => $user->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Owner->value,
        ]);

        $service = Mockery::mock(BillingServiceInterface::class);
        $service->shouldReceive('cancel')->once();
        $this->app->instance(BillingServiceInterface::class, $service);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->deleteJson('/api/v1/billing/subscription');

        $response->assertOk();
        $response->assertJsonFragment(['message' => 'Subscription cancellation scheduled.']);
    }
}
