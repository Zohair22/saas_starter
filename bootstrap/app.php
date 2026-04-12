<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Validation\ValidationException;
use Modules\Billing\Http\Middleware\EnsureFeatureLimit;
use Modules\Billing\Http\Middleware\EnsureTenantApiRateLimit;
use Modules\Tenant\Http\Middleware\EnsureTenantMembership;
use Modules\Tenant\Http\Middleware\IdentifyTenant;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        channels: __DIR__.'/../routes/channels.php',
        attributes: ['middleware' => ['auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxies = env('TRUSTED_PROXIES', '*');

        if (is_string($trustedProxies) && $trustedProxies !== '*') {
            $trustedProxies = array_values(array_filter(array_map('trim', explode(',', $trustedProxies))));
        }

        $middleware->trustProxies(
            at: $trustedProxies,
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->append(AddSecurityHeaders::class);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Resolve tenant context before implicit model binding, so tenant-scoped
        // models can be bound correctly on API routes.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: IdentifyTenant::class,
        );

        $middleware->alias([
            'tenant' => IdentifyTenant::class,
            'tenant.member' => EnsureTenantMembership::class,
            'tenant.lifecycle' => 'Modules\\Tenant\\Http\\Middleware\\EnsureTenantLifecycleAccess',
            'feature.limit' => EnsureFeatureLimit::class,
            'tenant.api.rate' => EnsureTenantApiRateLimit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $exception): bool {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (ValidationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], $exception->status);
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage() ?: 'This action is unauthorized.',
            ], 403);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage() ?: 'Resource not found.',
            ], 404);
        });

        $exceptions->render(function (TooManyRequestsHttpException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage() ?: 'Too many requests.',
            ], 429);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $statusCode = $exception->getStatusCode();

            return response()->json([
                'message' => $exception->getMessage() ?: 'Request failed.',
            ], $statusCode);
        });

        $exceptions->render(function (Throwable $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $status = 500;
            $message = app()->hasDebugModeEnabled()
                ? $exception->getMessage()
                : 'Server error.';

            return response()->json([
                'message' => $message,
            ], $status);
        });
    })->create();
