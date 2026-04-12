<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Services\UsageCounterService;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Project\Models\Project;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class UsageCounterServiceTest extends TestCase
{
    use RefreshDatabase;

    private UsageCounterService $service;

    private Tenants $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UsageCounterService;

        $owner = User::factory()->create();
        $this->tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme-usage-counter',
            'owner_id' => $owner->id,
        ]);

        request()->attributes->set('tenant', $this->tenant);
    }

    public function test_increment_users_increases_users_count(): void
    {
        $this->service->incrementUsers($this->tenant->id);

        $this->assertDatabaseHas('tenant_usage_counters', [
            'tenant_id' => $this->tenant->id,
            'users_count' => 1,
        ]);
    }

    public function test_decrement_users_decreases_users_count(): void
    {
        $this->service->incrementUsers($this->tenant->id, 3);
        $this->service->decrementUsers($this->tenant->id);

        $this->assertDatabaseHas('tenant_usage_counters', [
            'tenant_id' => $this->tenant->id,
            'users_count' => 2,
        ]);
    }

    public function test_decrement_users_does_not_go_below_zero(): void
    {
        $this->service->decrementUsers($this->tenant->id, 10);

        $this->assertDatabaseHas('tenant_usage_counters', [
            'tenant_id' => $this->tenant->id,
            'users_count' => 0,
        ]);
    }

    public function test_increment_projects_increases_projects_count(): void
    {
        $this->service->incrementProjects($this->tenant->id, 2);

        $this->assertDatabaseHas('tenant_usage_counters', [
            'tenant_id' => $this->tenant->id,
            'projects_count' => 2,
        ]);
    }

    public function test_decrement_projects_decreases_projects_count(): void
    {
        $this->service->incrementProjects($this->tenant->id, 4);
        $this->service->decrementProjects($this->tenant->id);

        $this->assertDatabaseHas('tenant_usage_counters', [
            'tenant_id' => $this->tenant->id,
            'projects_count' => 3,
        ]);
    }

    public function test_increment_api_requests_increases_api_requests_count(): void
    {
        $this->service->incrementApiRequests($this->tenant->id);
        $this->service->incrementApiRequests($this->tenant->id);

        $this->assertDatabaseHas('tenant_usage_counters', [
            'tenant_id' => $this->tenant->id,
            'api_requests_count' => 2,
        ]);
    }

    public function test_get_current_period_usage_returns_users_count(): void
    {
        $this->service->incrementUsers($this->tenant->id, 5);

        $usage = $this->service->getCurrentPeriodUsage($this->tenant->id, 'max_users');

        $this->assertSame(5, $usage);
    }

    public function test_get_current_period_usage_returns_zero_for_unknown_feature(): void
    {
        $usage = $this->service->getCurrentPeriodUsage($this->tenant->id, 'unknown_feature');

        $this->assertSame(0, $usage);
    }

    public function test_sync_tenant_usage_updates_counters_from_real_counts(): void
    {
        $member = User::factory()->create();

        Membership::query()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $member->id,
            'role' => MembershipRole::Member->value,
        ]);

        Project::factory()->create(['tenant_id' => $this->tenant->id, 'created_by' => $member->id]);
        Project::factory()->create(['tenant_id' => $this->tenant->id, 'created_by' => $member->id]);

        $this->service->syncTenantUsage($this->tenant->id);

        $this->assertSame(1, $this->service->getCurrentPeriodUsage($this->tenant->id, 'max_users'));
        $this->assertSame(2, $this->service->getCurrentPeriodUsage($this->tenant->id, 'max_projects'));
    }
}
