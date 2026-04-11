<?php

namespace Tests\Unit\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Subscription;
use Mockery;
use Mockery\MockInterface;
use Modules\Billing\Classes\DTOs\CreateSubscriptionData;
use Modules\Billing\Classes\DTOs\SwapSubscriptionData;
use Modules\Billing\Interfaces\Contracts\BillingRepositoryInterface;
use Modules\Billing\Models\Plan;
use Modules\Billing\Services\BillingService;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Project\Models\Project;
use Modules\User\Models\User;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_plans_delegates_to_repository(): void
    {
        /** @var BillingRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(BillingRepositoryInterface::class);
        $service = new BillingService($repository);

        $plans = new Collection;
        $repository->shouldReceive('listPlans')->once()->andReturn($plans);

        $result = $service->listPlans();

        $this->assertSame($plans, $result);
    }

    public function test_current_subscription_delegates_to_repository(): void
    {
        /** @var BillingRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(BillingRepositoryInterface::class);
        $service = new BillingService($repository);

        [$tenant] = $this->createTenantWithMember(MembershipRole::Owner);
        $subscription = Mockery::mock(Subscription::class);

        $repository
            ->shouldReceive('currentSubscription')
            ->once()
            ->with($tenant)
            ->andReturn($subscription);

        $result = $service->currentSubscription($tenant);

        $this->assertSame($subscription, $result);
    }

    public function test_subscribe_throws_when_users_exceed_plan_limit(): void
    {
        /** @var BillingRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(BillingRepositoryInterface::class);
        $service = new BillingService($repository);

        [$tenant, $owner] = $this->createTenantWithMember(MembershipRole::Owner);
        $member = User::factory()->create();

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $member->id,
            'role' => MembershipRole::Member->value,
        ]);

        $plan = new Plan([
            'code' => 'starter',
            'name' => 'Starter',
            'max_users' => 1,
            'max_projects' => 10,
            'api_rate_limit' => 100,
            'is_active' => true,
        ]);
        $data = new CreateSubscriptionData('starter', 'pm_card_visa');

        request()->attributes->set('tenant', $tenant);

        $repository->shouldReceive('findPlanByCode')->once()->with('starter')->andReturn($plan);

        $this->expectException(ValidationException::class);

        $service->subscribe($tenant, $data);
    }

    public function test_subscribe_throws_when_projects_exceed_plan_limit(): void
    {
        /** @var BillingRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(BillingRepositoryInterface::class);
        $service = new BillingService($repository);

        [$tenant, $owner] = $this->createTenantWithMember(MembershipRole::Owner);
        Project::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $owner->id]);
        Project::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $owner->id]);

        $plan = new Plan([
            'code' => 'starter',
            'name' => 'Starter',
            'max_users' => 10,
            'max_projects' => 1,
            'api_rate_limit' => 100,
            'is_active' => true,
        ]);
        $data = new CreateSubscriptionData('starter', 'pm_card_visa');

        request()->attributes->set('tenant', $tenant);

        $repository->shouldReceive('findPlanByCode')->once()->with('starter')->andReturn($plan);

        $this->expectException(ValidationException::class);

        $service->subscribe($tenant, $data);
    }

    public function test_subscribe_calls_repository_when_within_plan_limits(): void
    {
        /** @var BillingRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(BillingRepositoryInterface::class);
        $service = new BillingService($repository);

        [$tenant] = $this->createTenantWithMember(MembershipRole::Owner);
        $plan = new Plan([
            'code' => 'pro',
            'name' => 'Pro',
            'max_users' => 10,
            'max_projects' => 10,
            'api_rate_limit' => 100,
            'is_active' => true,
        ]);
        $data = new CreateSubscriptionData('pro', 'pm_card_visa');
        $subscription = Mockery::mock(Subscription::class);

        request()->attributes->set('tenant', $tenant);

        $repository->shouldReceive('findPlanByCode')->once()->with('pro')->andReturn($plan);
        $repository->shouldReceive('subscribe')->once()->with($tenant, $plan, 'pm_card_visa')->andReturn($subscription);

        $result = $service->subscribe($tenant, $data);

        $this->assertSame($subscription, $result);
    }

    public function test_swap_validates_limits_and_calls_repository(): void
    {
        /** @var BillingRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(BillingRepositoryInterface::class);
        $service = new BillingService($repository);

        [$tenant] = $this->createTenantWithMember(MembershipRole::Owner);
        $plan = new Plan([
            'code' => 'growth',
            'name' => 'Growth',
            'max_users' => 10,
            'max_projects' => 10,
            'api_rate_limit' => 100,
            'is_active' => true,
        ]);
        $data = new SwapSubscriptionData('growth');
        $subscription = Mockery::mock(Subscription::class);

        request()->attributes->set('tenant', $tenant);

        $repository->shouldReceive('findPlanByCode')->once()->with('growth')->andReturn($plan);
        $repository->shouldReceive('swap')->once()->with($tenant, $plan)->andReturn($subscription);

        $result = $service->swap($tenant, $data);

        $this->assertSame($subscription, $result);
    }

    public function test_cancel_delegates_to_repository(): void
    {
        /** @var BillingRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(BillingRepositoryInterface::class);
        $service = new BillingService($repository);

        [$tenant] = $this->createTenantWithMember(MembershipRole::Owner);

        $repository->shouldReceive('cancel')->once()->with($tenant);

        $service->cancel($tenant);
    }
}
