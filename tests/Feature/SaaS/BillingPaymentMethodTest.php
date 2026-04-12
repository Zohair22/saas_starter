<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Membership\Enums\MembershipRole;
use Modules\User\Models\User;
use Tests\TestCase;

class BillingPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_payment_methods(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Owner);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/v1/billing/payment-methods');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'default_payment_method']);
    }

    public function test_member_can_view_payment_methods(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Member);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/v1/billing/payment-methods');

        $response->assertOk();
    }

    public function test_payment_methods_returns_empty_when_no_stripe_id(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Owner);

        $this->assertNull($tenant->stripe_id);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/v1/billing/payment-methods');

        $response->assertOk();
        $response->assertExactJson(['data' => [], 'default_payment_method' => null]);
    }

    public function test_member_cannot_add_payment_method(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Member);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/billing/payment-methods', [
                'payment_method' => 'pm_card_visa',
            ]);

        $response->assertForbidden();
    }

    public function test_member_cannot_set_default_payment_method(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Member);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->patchJson('/api/v1/billing/payment-methods/default', [
                'payment_method' => 'pm_card_visa',
            ]);

        $response->assertForbidden();
    }

    public function test_member_cannot_remove_payment_method(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Member);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->deleteJson('/api/v1/billing/payment-methods/pm_card_visa');

        $response->assertForbidden();
    }

    public function test_add_payment_method_requires_pm_prefix(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Owner);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/billing/payment-methods', [
                'payment_method' => 'tok_visa',
            ]);

        $response->assertUnprocessable();
    }

    public function test_set_default_payment_method_requires_pm_prefix(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Owner);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->patchJson('/api/v1/billing/payment-methods/default', [
                'payment_method' => 'not-a-pm',
            ]);

        $response->assertUnprocessable();
    }

    public function test_user_outside_tenant_cannot_access_payment_methods(): void
    {
        [$tenant] = $this->createTenantWithMember(MembershipRole::Owner);

        $outsider = User::factory()->create();
        Sanctum::actingAs($outsider);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/v1/billing/payment-methods');

        $response->assertForbidden();
    }
}
