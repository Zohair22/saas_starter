<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Modules\Billing\Interfaces\Contracts\UsageCounterServiceInterface;
use Modules\Billing\Models\Plan;
use Modules\Billing\Services\FeatureLimitService;
use Modules\Membership\Enums\MembershipRole;
use Tests\TestCase;

class FeatureLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_limit_returns_unlimited_when_tenant_has_no_plan(): void
    {
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new FeatureLimitService($usageCounterService);

        [$tenant] = $this->createTenantWithMember(MembershipRole::Owner);
        $tenant->setRelation('plan', null);

        $limit = $service->getLimit($tenant, 'max_users');

        $this->assertSame(PHP_INT_MAX, $limit);
    }

    public function test_get_limit_returns_value_from_plan_for_known_feature(): void
    {
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new FeatureLimitService($usageCounterService);

        [$tenant] = $this->createTenantWithMember(MembershipRole::Owner);
        $plan = new Plan([
            'max_users' => 7,
            'max_projects' => 15,
            'api_rate_limit' => 120,
        ]);
        $tenant->setRelation('plan', $plan);

        $limit = $service->getLimit($tenant, 'max_users');

        $this->assertSame(7, $limit);
    }

    public function test_get_limit_returns_unlimited_for_unknown_feature(): void
    {
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new FeatureLimitService($usageCounterService);

        [$tenant] = $this->createTenantWithMember(MembershipRole::Owner);
        $tenant->setRelation('plan', new Plan(['max_users' => 1, 'max_projects' => 1, 'api_rate_limit' => 1]));

        $limit = $service->getLimit($tenant, 'unknown_feature');

        $this->assertSame(PHP_INT_MAX, $limit);
    }

    public function test_get_current_usage_syncs_and_reads_counter_for_max_users(): void
    {
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new FeatureLimitService($usageCounterService);

        [$tenant] = $this->createTenantWithMember(MembershipRole::Owner);

        $usageCounterService->shouldReceive('syncTenantUsage')->once()->with($tenant->id);
        $usageCounterService->shouldReceive('getCurrentPeriodUsage')->once()->with($tenant->id, 'max_users')->andReturn(3);

        $usage = $service->getCurrentUsage($tenant, 'max_users');

        $this->assertSame(3, $usage);
    }

    public function test_can_use_returns_true_when_usage_is_below_limit(): void
    {
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new FeatureLimitService($usageCounterService);

        [$tenant] = $this->createTenantWithMember(MembershipRole::Owner);
        $tenant->setRelation('plan', new Plan([
            'max_users' => 5,
            'max_projects' => 10,
            'api_rate_limit' => 120,
        ]));

        $usageCounterService->shouldReceive('syncTenantUsage')->once()->with($tenant->id);
        $usageCounterService->shouldReceive('getCurrentPeriodUsage')->once()->with($tenant->id, 'max_users')->andReturn(4);

        $this->assertTrue($service->canUse($tenant, 'max_users'));
    }

    public function test_can_use_returns_false_when_usage_reaches_limit(): void
    {
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new FeatureLimitService($usageCounterService);

        [$tenant] = $this->createTenantWithMember(MembershipRole::Owner);
        $tenant->setRelation('plan', new Plan([
            'max_users' => 5,
            'max_projects' => 10,
            'api_rate_limit' => 120,
        ]));

        $usageCounterService->shouldReceive('syncTenantUsage')->once()->with($tenant->id);
        $usageCounterService->shouldReceive('getCurrentPeriodUsage')->once()->with($tenant->id, 'max_users')->andReturn(5);

        $this->assertFalse($service->canUse($tenant, 'max_users'));
    }

    public function test_can_use_returns_true_for_unlimited_feature(): void
    {
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new FeatureLimitService($usageCounterService);

        [$tenant] = $this->createTenantWithMember(MembershipRole::Owner);
        $tenant->setRelation('plan', null);

        $this->assertTrue($service->canUse($tenant, 'max_users'));
    }
}
