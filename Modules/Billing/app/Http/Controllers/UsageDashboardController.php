<?php

namespace Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Billing\Interfaces\Contracts\FeatureLimitServiceInterface;
use Modules\Billing\Interfaces\Contracts\UsageCounterServiceInterface;
use Modules\Billing\Models\TenantUsageCounter;
use Modules\Tenant\Models\Tenants;

class UsageDashboardController extends Controller
{
    public function __construct(
        private readonly FeatureLimitServiceInterface $featureLimitService,
        private readonly UsageCounterServiceInterface $usageCounterService,
    ) {}

    public function show(): JsonResponse
    {
        /** @var Tenants $tenant */
        $tenant = request()->attributes->get('tenant');
        $this->authorize('manageSubscription', $tenant);

        $this->usageCounterService->syncTenantUsage($tenant->id);

        $limits = [
            'max_users' => $this->featureLimitService->getLimit($tenant, 'max_users'),
            'max_projects' => $this->featureLimitService->getLimit($tenant, 'max_projects'),
            'api_rate_limit' => $this->featureLimitService->getLimit($tenant, 'api_rate_limit'),
        ];

        $usage = [
            'max_users' => $this->featureLimitService->getCurrentUsage($tenant, 'max_users'),
            'max_projects' => $this->featureLimitService->getCurrentUsage($tenant, 'max_projects'),
            'api_rate_limit' => $this->featureLimitService->getCurrentUsage($tenant, 'api_rate_limit'),
        ];

        $history = TenantUsageCounter::query()
            ->latest('period_start')
            ->limit(6)
            ->get()
            ->map(fn (TenantUsageCounter $counter) => [
                'period_start' => $counter->period_start,
                'users_count' => $counter->users_count,
                'projects_count' => $counter->projects_count,
                'api_requests_count' => $counter->api_requests_count,
            ]);

        return response()->json([
            'plan' => [
                'id' => $tenant->plan?->id,
                'code' => $tenant->plan?->code,
                'name' => $tenant->plan?->name,
            ],
            'limits' => $limits,
            'usage' => $usage,
            'utilization' => [
                'max_users' => $this->utilization($usage['max_users'], $limits['max_users']),
                'max_projects' => $this->utilization($usage['max_projects'], $limits['max_projects']),
                'api_rate_limit' => $this->utilization($usage['api_rate_limit'], $limits['api_rate_limit']),
            ],
            'history' => $history,
        ]);
    }

    private function utilization(int $usage, int $limit): ?float
    {
        if ($limit <= 0 || $limit === PHP_INT_MAX) {
            return null;
        }

        return round(($usage / $limit) * 100, 2);
    }
}
