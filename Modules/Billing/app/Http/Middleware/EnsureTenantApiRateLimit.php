<?php

namespace Modules\Billing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Billing\Interfaces\Contracts\UsageCounterServiceInterface;
use Modules\Tenant\Models\Tenants;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantApiRateLimit
{
    public function __construct(
        private readonly UsageCounterServiceInterface $usageCounterService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Tenants|null $tenant */
        $tenant = $request->attributes->get('tenant');

        $response = $next($request);

        if ($tenant) {
            $this->usageCounterService->incrementApiRequests($tenant->id);
        }

        return $response;
    }
}
