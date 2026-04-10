<?php

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantMembership
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthenticated.');
        }

        $tenant = $request->attributes->get('tenant');

        if (! $tenant) {
            abort(Response::HTTP_NOT_FOUND, 'Tenant not found.');
        }

        if (! $user->memberships()->exists()) {
            abort(Response::HTTP_FORBIDDEN, 'You are not a member of this tenant.');
        }

        return $next($request);
    }
}
