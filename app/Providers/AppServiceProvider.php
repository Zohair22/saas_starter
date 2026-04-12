<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Models\Tenants;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            $email = strtolower((string) $request->input('email', 'guest'));

            return Limit::perMinute(10)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('stripe-webhook', function (Request $request) {
            $limit = (int) config('cashier.webhook.rate_limit', 120);
            $signature = (string) $request->header('Stripe-Signature', 'unsigned');

            return Limit::perMinute($limit)->by($signature.'|'.$request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            /** @var Tenants|null $tenant */
            $tenant = app()->has('tenant') ? app('tenant') : null;

            $limit = $tenant?->plan?->api_rate_limit ?? 60;

            return Limit::perMinute($limit)->by('tenant:'.($tenant?->id ?? $request->ip()));
        });
    }
}
