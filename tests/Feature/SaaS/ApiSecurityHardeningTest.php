<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_endpoint_is_rate_limited_with_auth_limiter(): void
    {
        $payload = [
            'email' => 'rate-limit@example.com',
            'password' => 'invalid-password',
        ];

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $response = $this->postJson('/api/v1/login', $payload);
            $response->assertStatus(401);
        }

        $this->postJson('/api/v1/login', $payload)->assertStatus(429);
    }

    public function test_billing_webhook_endpoint_is_rate_limited_with_dedicated_limiter(): void
    {
        config([
            'cashier.webhook.secret' => null,
            'cashier.webhook.rate_limit' => 2,
        ]);

        $payload = [
            'id' => 'evt_rate_limit_webhook_001',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_rate_limit_webhook_001',
                    'customer' => 'cus_rate_limit_webhook_001',
                ],
            ],
        ];

        $this->postJson('/api/v1/billing/webhook', $payload)->assertOk();
        $this->postJson('/api/v1/billing/webhook', $payload)->assertOk();
        $this->postJson('/api/v1/billing/webhook', $payload)->assertStatus(429);
    }

    public function test_billing_webhook_requires_valid_signature_when_secret_is_configured(): void
    {
        config([
            'cashier.webhook.secret' => 'whsec_test_secret',
            'cashier.webhook.rate_limit' => 120,
        ]);

        $payload = [
            'id' => 'evt_invalid_signature_001',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_invalid_signature_001',
                    'customer' => 'cus_invalid_signature_001',
                ],
            ],
        ];

        $this->postJson('/api/v1/billing/webhook', $payload)->assertForbidden();
    }

    public function test_billing_webhook_accepts_valid_signature_when_secret_is_configured(): void
    {
        $secret = 'whsec_valid_secret';

        config([
            'cashier.webhook.secret' => $secret,
            'cashier.webhook.rate_limit' => 120,
            'cashier.webhook.tolerance' => 300,
        ]);

        $payload = [
            'id' => 'evt_valid_signature_001',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_valid_signature_001',
                    'customer' => 'cus_valid_signature_001',
                ],
            ],
        ];

        $content = json_encode($payload);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$content, $secret);

        $response = $this->call(
            method: 'POST',
            uri: '/api/v1/billing/webhook',
            parameters: [],
            cookies: [],
            files: [],
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
            ],
            content: $content,
        );

        $response->assertOk();
    }

    public function test_cors_preflight_allows_only_configured_origins(): void
    {
        config([
            'cors.allowed_origins' => ['https://app.example.com'],
            'cors.supports_credentials' => true,
        ]);

        $allowed = $this
            ->withHeader('Origin', 'https://app.example.com')
            ->withHeader('Access-Control-Request-Method', 'POST')
            ->options('/api/v1/login');

        $allowed->assertStatus(204);
        $allowed->assertHeader('Access-Control-Allow-Origin', 'https://app.example.com');

        $blocked = $this
            ->withHeader('Origin', 'https://evil.example.com')
            ->withHeader('Access-Control-Request-Method', 'POST')
            ->options('/api/v1/login');

        $blocked->assertStatus(204);
        $this->assertNotSame(
            'https://evil.example.com',
            (string) $blocked->headers->get('Access-Control-Allow-Origin', '')
        );
    }

    public function test_forwarded_proto_is_trusted_for_secure_detection(): void
    {
        Route::get('/_proxy-secure-check', function (Request $request) {
            return response()->json([
                'secure' => $request->isSecure(),
            ]);
        });

        $response = $this
            ->withServerVariables([
                'HTTP_X_FORWARDED_PROTO' => 'https',
                'HTTP_X_FORWARDED_PORT' => '443',
            ])
            ->getJson('/_proxy-secure-check');

        $response->assertOk();
        $response->assertJsonPath('secure', true);
    }
}
