<?php

namespace Modules\Billing\Services;

use Modules\Billing\Interfaces\Contracts\FeatureLimitServiceInterface;
use Modules\Billing\Interfaces\Contracts\UsageCounterServiceInterface;
use Modules\Tenant\Models\Tenants;

class FeatureLimitService implements FeatureLimitServiceInterface
{
    private const FEATURE_MAP = [
        'max_users' => 'max_users',
        'max_projects' => 'max_projects',
        'api_rate_limit' => 'api_rate_limit',
    ];

    public function __construct(
        private readonly UsageCounterServiceInterface $usageCounterService,
    ) {}

    public function getLimit(Tenants $tenant, string $feature): int
    {
        $column = self::FEATURE_MAP[$feature] ?? null;

        if (! $column || ! $tenant->plan) {
            return PHP_INT_MAX;
        }

        return max(0, (int) data_get($tenant->plan, $column, 0));
    }

    public function getCurrentUsage(Tenants $tenant, string $feature): int
    {
        if (in_array($feature, ['max_users', 'max_projects'], true)) {
            $this->usageCounterService->syncTenantUsage($tenant->id);

            return $this->usageCounterService->getCurrentPeriodUsage($tenant->id, $feature);
        }

        if ($feature === 'api_rate_limit') {
            return $this->usageCounterService->getCurrentPeriodUsage($tenant->id, $feature);
        }

        return match ($feature) {
            'max_users' => $tenant->memberships()->count(),
            'max_projects' => $tenant->projects()->count(),
            default => 0,
        };
    }

    public function canUse(Tenants $tenant, string $feature): bool
    {
        $limit = $this->getLimit($tenant, $feature);

        if ($limit === PHP_INT_MAX) {
            return true;
        }

        return $this->getCurrentUsage($tenant, $feature) < $limit;
    }
}
