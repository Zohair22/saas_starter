<?php

namespace Modules\Billing\Services;

use Modules\Billing\Interfaces\Contracts\UsageCounterServiceInterface;
use Modules\Billing\Models\TenantUsageCounter;
use Modules\Membership\Models\Membership;
use Modules\Project\Models\Project;
use Modules\Tenant\Models\Tenants;

class UsageCounterService implements UsageCounterServiceInterface
{
    private const FEATURE_TO_COLUMN = [
        'max_users' => 'users_count',
        'max_projects' => 'projects_count',
        'api_rate_limit' => 'api_requests_count',
    ];

    public function incrementUsers(int $tenantId, int $by = 1): void
    {
        $this->incrementColumn($tenantId, 'users_count', $by);
    }

    public function decrementUsers(int $tenantId, int $by = 1): void
    {
        $this->decrementColumn($tenantId, 'users_count', $by);
    }

    public function incrementProjects(int $tenantId, int $by = 1): void
    {
        $this->incrementColumn($tenantId, 'projects_count', $by);
    }

    public function decrementProjects(int $tenantId, int $by = 1): void
    {
        $this->decrementColumn($tenantId, 'projects_count', $by);
    }

    public function incrementApiRequests(int $tenantId, int $by = 1): void
    {
        $this->incrementColumn($tenantId, 'api_requests_count', $by);
    }

    public function getCurrentPeriodUsage(int $tenantId, string $feature): int
    {
        $column = self::FEATURE_TO_COLUMN[$feature] ?? null;

        if (! $column) {
            return 0;
        }

        $counter = $this->resolveCurrentPeriodCounter($tenantId);

        return (int) data_get($counter, $column, 0);
    }

    public function syncTenantUsage(int $tenantId): void
    {
        $this->runForTenant($tenantId, function () use ($tenantId): void {
            $counter = $this->resolveCurrentPeriodCounter($tenantId);

            $counter->update([
                'users_count' => Membership::query()->count(),
                'projects_count' => Project::query()->count(),
            ]);
        });
    }

    private function incrementColumn(int $tenantId, string $column, int $by): void
    {
        $counter = $this->resolveCurrentPeriodCounter($tenantId);
        $counter->increment($column, max(0, $by));
    }

    private function decrementColumn(int $tenantId, string $column, int $by): void
    {
        $counter = $this->resolveCurrentPeriodCounter($tenantId);
        $currentValue = (int) $counter->getAttribute($column);
        $counter->update([$column => max(0, $currentValue - max(0, $by))]);
    }

    private function resolveCurrentPeriodCounter(int $tenantId): TenantUsageCounter
    {
        $periodStart = now()->startOfMonth()->toDateString();

        return $this->runForTenant($tenantId, function () use ($tenantId, $periodStart): TenantUsageCounter {
            TenantUsageCounter::query()->upsert(
                values: [[
                    'tenant_id' => $tenantId,
                    'period_start' => $periodStart,
                    'users_count' => 0,
                    'projects_count' => 0,
                    'api_requests_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]],
                uniqueBy: ['tenant_id', 'period_start'],
                update: []
            );

            return TenantUsageCounter::query()
                ->where('period_start', $periodStart)
                ->firstOrFail();
        });
    }

    /**
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    private function runForTenant(int $tenantId, callable $callback): mixed
    {
        $previousTenant = app()->has('tenant') ? app('tenant') : null;
        $tenant = Tenants::query()->findOrFail($tenantId);

        app()->instance('tenant', $tenant);

        try {
            return $callback();
        } finally {
            if ($previousTenant) {
                app()->instance('tenant', $previousTenant);
            } else {
                app()->forgetInstance('tenant');
            }
        }
    }
}
