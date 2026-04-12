<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Modules\Billing\Interfaces\Contracts\BillingServiceInterface;
use Modules\Billing\Models\Plan;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Modules\User\Services\MfaService;
use Tests\TestCase;

class StepUpAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_mutation_requires_step_up_when_mfa_is_enabled(): void
    {
        [$owner, $tenant] = $this->createMfaEnabledOwner();

        Plan::query()->create([
            'code' => 'pro',
            'name' => 'Pro',
            'stripe_price_id' => 'price_pro_test',
            'max_users' => 10,
            'max_projects' => 10,
            'api_rate_limit' => 1000,
            'is_active' => true,
        ]);

        Sanctum::actingAs($owner);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/billing/subscribe', [
                'plan_code' => 'pro',
                'payment_method' => 'pm_card_visa',
            ]);

        $response->assertStatus(428)
            ->assertJsonPath('step_up_required', true);
    }

    public function test_billing_mutation_accepts_valid_step_up_code(): void
    {
        [$owner, $tenant, $secret] = $this->createMfaEnabledOwner();

        Plan::query()->create([
            'code' => 'pro',
            'name' => 'Pro',
            'stripe_price_id' => 'price_pro_test',
            'max_users' => 10,
            'max_projects' => 10,
            'api_rate_limit' => 1000,
            'is_active' => true,
        ]);

        $service = Mockery::mock(BillingServiceInterface::class);
        $service->shouldReceive('subscribe')->once()->andReturn(null);
        $this->app->instance(BillingServiceInterface::class, $service);

        $mfaCode = app(MfaService::class)->currentCodeForSecret($secret);

        Sanctum::actingAs($owner);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/billing/subscribe', [
                'plan_code' => 'pro',
                'payment_method' => 'pm_card_visa',
                'mfa_code' => $mfaCode,
            ]);

        $response->assertCreated();
    }

    public function test_admin_impersonation_requires_step_up_when_mfa_is_enabled(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'mfa_enabled' => true,
            'mfa_secret' => 'JBSWY3DPEHPK3PXP',
            'mfa_recovery_codes' => null,
        ]);

        $target = User::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/admin/impersonate/{$target->id}");

        $response->assertStatus(428)
            ->assertJsonPath('step_up_required', true);
    }

    public function test_admin_impersonation_accepts_valid_step_up_code(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';

        $admin = User::factory()->create([
            'is_super_admin' => true,
            'mfa_enabled' => true,
            'mfa_secret' => $secret,
            'mfa_recovery_codes' => null,
        ]);

        $target = User::factory()->create();
        $mfaCode = app(MfaService::class)->currentCodeForSecret($secret);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/admin/impersonate/{$target->id}", [
            'mfa_code' => $mfaCode,
        ]);

        $response->assertOk()
            ->assertJsonPath('impersonated_user.id', $target->id);
    }

    /**
     * @return array{0: User, 1: Tenants, 2: string}
     */
    private function createMfaEnabledOwner(): array
    {
        $secret = 'JBSWY3DPEHPK3PXP';

        $owner = User::factory()->create([
            'mfa_enabled' => true,
            'mfa_secret' => $secret,
            'mfa_recovery_codes' => null,
        ]);

        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme-'.str()->random(5),
            'owner_id' => $owner->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::Owner->value,
        ]);

        return [$owner, $tenant, $secret];
    }
}
