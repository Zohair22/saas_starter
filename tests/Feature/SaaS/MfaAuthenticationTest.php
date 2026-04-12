<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\User\Models\User;
use Modules\User\Services\MfaService;
use Tests\TestCase;

class MfaAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_setup_and_enable_mfa_then_login_with_totp(): void
    {
        $password = 'password123';
        $user = User::factory()->create([
            'email' => 'mfa@example.com',
            'password' => $password,
        ]);

        Sanctum::actingAs($user);

        $setupResponse = $this->postJson('/api/v1/auth/mfa/setup');
        $setupResponse->assertOk();

        $secret = (string) $setupResponse->json('secret');
        $recoveryCodes = $setupResponse->json('recovery_codes');

        $this->assertNotSame('', $secret);
        $this->assertIsArray($recoveryCodes);
        $this->assertCount(8, $recoveryCodes);

        /** @var MfaService $mfaService */
        $mfaService = app(MfaService::class);
        $code = $mfaService->currentCodeForSecret($secret);

        $this->postJson('/api/v1/auth/mfa/enable', [
            'code' => $code,
        ])->assertOk();

        $user->refresh();
        $this->assertTrue((bool) $user->mfa_enabled);

        $noMfaLogin = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => $password,
        ]);
        $noMfaLogin->assertStatus(422)
            ->assertJsonPath('mfa_required', true);

        $withMfaLogin = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => $password,
            'mfa_code' => $mfaService->currentCodeForSecret($secret),
        ]);

        $withMfaLogin->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonStructure(['token']);
    }

    public function test_recovery_code_can_be_used_only_once_for_login(): void
    {
        $password = 'password123';
        $user = User::factory()->create([
            'email' => 'mfa-recovery@example.com',
            'password' => $password,
        ]);

        Sanctum::actingAs($user);

        $setupResponse = $this->postJson('/api/v1/auth/mfa/setup');
        $setupResponse->assertOk();

        $secret = (string) $setupResponse->json('secret');
        $firstRecoveryCode = (string) ($setupResponse->json('recovery_codes.0') ?? '');

        /** @var MfaService $mfaService */
        $mfaService = app(MfaService::class);
        $code = $mfaService->currentCodeForSecret($secret);

        $this->postJson('/api/v1/auth/mfa/enable', [
            'code' => $code,
        ])->assertOk();

        $firstLogin = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => $password,
            'mfa_recovery_code' => $firstRecoveryCode,
        ]);

        $firstLogin->assertOk();

        $secondLogin = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => $password,
            'mfa_recovery_code' => $firstRecoveryCode,
        ]);

        $secondLogin->assertStatus(422)
            ->assertJsonPath('mfa_required', true);
    }

    public function test_user_can_disable_mfa_with_valid_password(): void
    {
        $password = 'password123';
        $user = User::factory()->create([
            'password' => $password,
        ]);

        Sanctum::actingAs($user);

        $setupResponse = $this->postJson('/api/v1/auth/mfa/setup');
        $secret = (string) $setupResponse->json('secret');

        /** @var MfaService $mfaService */
        $mfaService = app(MfaService::class);

        $this->postJson('/api/v1/auth/mfa/enable', [
            'code' => $mfaService->currentCodeForSecret($secret),
        ])->assertOk();

        $disable = $this->postJson('/api/v1/auth/mfa/disable', [
            'password' => $password,
        ]);

        $disable->assertOk();

        $user->refresh();

        $this->assertFalse((bool) $user->mfa_enabled);
        $this->assertNull($user->mfa_secret);
        $this->assertNull($user->mfa_recovery_codes);
    }
}
