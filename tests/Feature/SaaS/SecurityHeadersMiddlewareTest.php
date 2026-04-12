<?php

namespace Tests\Feature\SaaS;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    public function test_security_headers_are_added_to_web_responses(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeader('Content-Security-Policy', "frame-ancestors 'none'");
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_existing_security_headers_are_not_overwritten(): void
    {
        Route::get('/_security-headers-check', function () {
            return response('ok')->header('X-Frame-Options', 'SAMEORIGIN');
        });

        $response = $this->get('/_security-headers-check');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
