<?php

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Tenants;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Resolve tenant from subdomain first, then X-Tenant-ID fallback.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantFromSubdomain = $this->resolveFromSubdomain($request->getHost());
        $tenantFromHeader = $this->resolveFromHeader($request);

        if ($tenantFromSubdomain && $tenantFromHeader && $tenantFromSubdomain->id !== $tenantFromHeader->id) {
            abort(Response::HTTP_CONFLICT, 'Tenant identifier mismatch.');
        }

        $tenant = $tenantFromSubdomain ?? $tenantFromHeader;

        if (! $tenant) {
            abort(Response::HTTP_NOT_FOUND, 'Tenant not found.');
        }

        $request->attributes->set('tenant', $tenant);
        app()->instance('tenant', $tenant);

        return $next($request);
    }

    private function resolveFromSubdomain(string $host): ?Tenants
    {
        $parts = explode('.', $host);

        // Require at least subdomain.domain.tld shape.
        if (count($parts) < 3) {
            return null;
        }

        $subdomain = strtolower($parts[0]);

        if ($subdomain === 'www') {
            return null;
        }

        return Tenants::query()->where('slug', $subdomain)->first();
    }

    private function resolveFromHeader(Request $request): ?Tenants
    {
        $tenantIdentifier = $request->header('X-Tenant-ID');

        if (! $tenantIdentifier) {
            return null;
        }

        if (ctype_digit($tenantIdentifier)) {
            return Tenants::query()->find((int) $tenantIdentifier);
        }

        return Tenants::query()->where('slug', strtolower($tenantIdentifier))->first();
    }
}
