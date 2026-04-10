<?php

namespace Modules\Billing\Console;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Modules\Billing\Models\TenantUsageCounter;
use Modules\Membership\Models\Membership;
use Modules\Project\Models\Project;
use Modules\Tenant\Models\Tenants;

class UsageCountersRolloverCommand extends Command
{
    protected $signature = 'billing:usage-counters-rollover
                            {--month= : Target month in YYYY-MM format. Defaults to current month}
                            {--backfill : Backfill users/projects from current database state}';

    protected $description = 'Create or backfill monthly tenant usage counters.';

    public function handle(): int
    {
        $periodStart = $this->resolveTargetPeriodStart();
        $tenantIds = Tenants::query()->pluck('id');
        $created = 0;
        $updated = 0;

        foreach ($tenantIds as $tenantId) {
            $existing = $this->runForTenant((int) $tenantId, fn () => TenantUsageCounter::query()
                ->whereDate('period_start', $periodStart->toDateString())
                ->first());

            [$usersCount, $projectsCount] = $this->runForTenant(
                (int) $tenantId,
                fn () => $this->resolveSeedCounts($periodStart)
            );

            if (! $existing) {
                $this->runForTenant((int) $tenantId, fn () => TenantUsageCounter::query()->create([
                    'period_start' => $periodStart->toDateString(),
                    'users_count' => $usersCount,
                    'projects_count' => $projectsCount,
                    'api_requests_count' => 0,
                ]));

                $created++;

                continue;
            }

            if ($this->option('backfill')) {
                $existing->update([
                    'users_count' => $usersCount,
                    'projects_count' => $projectsCount,
                ]);

                $updated++;
            }
        }

        $this->info(sprintf(
            'Usage counters rollover complete for %s. created=%d updated=%d',
            $periodStart->format('Y-m'),
            $created,
            $updated
        ));

        return self::SUCCESS;
    }

    private function resolveTargetPeriodStart(): CarbonImmutable
    {
        $monthOption = $this->option('month');

        if (! $monthOption) {
            return now()->startOfMonth()->toImmutable();
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m', $monthOption)->startOfMonth();
        } catch (\Throwable) {
            $this->fail('Invalid --month value. Use YYYY-MM format.');
        }
    }

    /**
     * @return array{0:int,1:int}
     */
    private function resolveSeedCounts(CarbonImmutable $periodStart): array
    {
        if ($this->option('backfill')) {
            return [
                Membership::query()->count(),
                Project::query()->count(),
            ];
        }

        $previous = TenantUsageCounter::query()
            ->whereDate('period_start', $periodStart->subMonth()->toDateString())
            ->first();

        if ($previous) {
            return [
                (int) $previous->users_count,
                (int) $previous->projects_count,
            ];
        }

        return [
            Membership::query()->count(),
            Project::query()->count(),
        ];
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
