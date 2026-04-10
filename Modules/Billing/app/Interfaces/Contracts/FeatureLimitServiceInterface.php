<?php

namespace Modules\Billing\Interfaces\Contracts;

use Modules\Tenant\Models\Tenants;

interface FeatureLimitServiceInterface
{
    public function getLimit(Tenants $tenant, string $feature): int;

    public function getCurrentUsage(Tenants $tenant, string $feature): int;

    public function canUse(Tenants $tenant, string $feature): bool;
}
