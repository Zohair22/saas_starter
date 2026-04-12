<?php

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Tenants;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantLifecycleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Tenants|null $tenant */
        $tenant = $request->attributes->get('tenant');

        if (! $tenant) {
            return $next($request);
        }

        if (! $this->isMutatingRequest($request) || $this->isBillingRoute($request)) {
            return $next($request);
        }

        if (! $this->isWriteLocked($tenant)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Tenant is currently restricted due to billing lifecycle status.',
            'lifecycle' => [
                'billing_status' => $tenant->billing_status,
                'grace_period_ends_at' => $tenant->grace_period_ends_at?->toISOString(),
                'is_write_locked' => true,
            ],
        ], Response::HTTP_LOCKED);
    }

    private function isMutatingRequest(Request $request): bool
    {
        return in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function isBillingRoute(Request $request): bool
    {
        return str_starts_with($request->path(), 'api/v1/billing');
    }

    private function isWriteLocked(Tenants $tenant): bool
    {
        $status = (string) ($tenant->billing_status ?? '');

        if ($status === '' || $status === 'active') {
            return false;
        }

        if ($tenant->grace_period_ends_at && $tenant->grace_period_ends_at->isFuture()) {
            return false;
        }

        return in_array($status, ['past_due', 'canceled', 'downgraded', 'unpaid', 'incomplete'], true);
    }
}
