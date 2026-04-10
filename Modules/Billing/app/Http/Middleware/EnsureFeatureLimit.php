<?php

namespace Modules\Billing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Billing\Interfaces\Contracts\FeatureLimitServiceInterface;
use Modules\Tenant\Models\Tenants;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureLimit
{
    public function __construct(
        private readonly FeatureLimitServiceInterface $featureLimitService,
    ) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        /** @var Tenants|null $tenant */
        $tenant = $request->attributes->get('tenant');

        if (! $tenant) {
            return response()->json([
                'message' => 'Tenant context is required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (! $this->featureLimitService->canUse($tenant, $feature)) {
            return response()->json([
                'message' => 'Feature limit reached.',
                'feature' => $feature,
                'limit' => $this->featureLimitService->getLimit($tenant, $feature),
                'usage' => $this->featureLimitService->getCurrentUsage($tenant, $feature),
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
