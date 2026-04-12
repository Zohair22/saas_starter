<?php

namespace Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionBuilder;
use Mockery;
use Modules\Billing\Models\Plan;
use Modules\Billing\Repositories\BillingRepository;
use Modules\Tenant\Models\Tenants;
use RuntimeException;
use Tests\TestCase;

class BillingRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_does_not_update_plan_when_paid_subscription_creation_fails(): void
    {
        $repository = new BillingRepository;

        $tenant = Mockery::mock(Tenants::class);
        $subscriptionBuilder = Mockery::mock(SubscriptionBuilder::class);

        $plan = Plan::query()->create([
            'id' => 42,
            'code' => 'enterprise',
            'name' => 'Enterprise',
            'stripe_price_id' => 'price_enterprise_test',
            'max_users' => 100,
            'max_projects' => 100,
            'api_rate_limit' => 10000,
            'is_active' => true,
        ]);

        $tenant
            ->shouldReceive('newSubscription')
            ->once()
            ->with('default', 'price_enterprise_test')
            ->andReturn($subscriptionBuilder);

        $subscriptionBuilder
            ->shouldReceive('create')
            ->once()
            ->with('pm_card_visa')
            ->andThrow(new RuntimeException('Payment requires additional action.'));

        $tenant->shouldNotReceive('update');

        $this->expectException(RuntimeException::class);

        $repository->subscribe($tenant, $plan, 'pm_card_visa');
    }

    public function test_subscribe_updates_plan_after_successful_paid_subscription_creation(): void
    {
        $repository = new BillingRepository;

        $tenant = Mockery::mock(Tenants::class);
        $subscriptionBuilder = Mockery::mock(SubscriptionBuilder::class);
        $subscription = Mockery::mock(Subscription::class);

        $plan = Plan::query()->create([
            'code' => 'pro',
            'name' => 'Pro',
            'stripe_price_id' => 'price_pro_test',
            'max_users' => 10,
            'max_projects' => 10,
            'api_rate_limit' => 1000,
            'is_active' => true,
        ]);

        $tenant
            ->shouldReceive('newSubscription')
            ->once()
            ->with('default', 'price_pro_test')
            ->andReturn($subscriptionBuilder);

        $subscriptionBuilder
            ->shouldReceive('create')
            ->once()
            ->with('pm_card_visa')
            ->andReturn($subscription);

        $tenant
            ->shouldReceive('update')
            ->once()
            ->with(['plan_id' => $plan->id]);

        $result = $repository->subscribe($tenant, $plan, 'pm_card_visa');

        $this->assertSame($subscription, $result);
    }
}
