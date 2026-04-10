<?php

namespace Modules\Billing\Interfaces\Contracts;

interface UsageCounterServiceInterface
{
    public function incrementUsers(int $tenantId, int $by = 1): void;

    public function decrementUsers(int $tenantId, int $by = 1): void;

    public function incrementProjects(int $tenantId, int $by = 1): void;

    public function decrementProjects(int $tenantId, int $by = 1): void;

    public function incrementApiRequests(int $tenantId, int $by = 1): void;

    public function getCurrentPeriodUsage(int $tenantId, string $feature): int;

    public function syncTenantUsage(int $tenantId): void;
}
