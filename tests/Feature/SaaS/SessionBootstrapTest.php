<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class SessionBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_returns_active_tenant_context_from_header(): void
    {
        $user = User::factory()->create();

        $tenantAlpha = Tenants::query()->create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'owner_id' => $user->id,
            'billing_status' => 'active',
        ]);

        $tenantBeta = Tenants::query()->create([
            'name' => 'Beta',
            'slug' => 'beta',
            'owner_id' => $user->id,
            'billing_status' => 'canceled',
            'grace_period_ends_at' => now()->subDay(),
        ]);

        Membership::query()->create([
            'tenant_id' => $tenantAlpha->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Member->value,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenantBeta->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Admin->value,
        ]);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenantBeta->id)
            ->getJson('/api/v1/session/bootstrap');

        $response->assertOk()
            ->assertJsonPath('active_tenant_id', $tenantBeta->id)
            ->assertJsonPath('context.current_membership.role', MembershipRole::Admin->value)
            ->assertJsonPath('context.capabilities.can_manage_projects', true)
            ->assertJsonPath('context.capabilities.can_manage_billing', true)
            ->assertJsonPath('tenants.1.lifecycle.is_write_locked', true);
    }

    public function test_bootstrap_falls_back_to_first_member_tenant_for_invalid_header(): void
    {
        $user = User::factory()->create();

        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'owner_id' => $user->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Member->value,
        ]);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', 'unknown-tenant')
            ->getJson('/api/v1/session/bootstrap');

        $response->assertOk()
            ->assertJsonPath('active_tenant_id', $tenant->id)
            ->assertJsonPath('context.capabilities.is_tenant_member', true);
    }

    public function test_bootstrap_returns_empty_context_for_user_without_tenants(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/session/bootstrap');

        $response->assertOk()
            ->assertJsonPath('active_tenant_id', null)
            ->assertJsonPath('tenants', [])
            ->assertJsonPath('context.current_membership', null)
            ->assertJsonPath('context.capabilities.is_tenant_member', false);
    }

    public function test_bootstrap_supports_conditional_requests_with_etag(): void
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme-etag',
            'owner_id' => $user->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Member->value,
        ]);

        Sanctum::actingAs($user);

        $initial = $this->getJson('/api/v1/session/bootstrap');

        $initial->assertOk();
        $this->assertNotNull($initial->headers->get('ETag'));
        $cacheControl = (string) $initial->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('max-age=15', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);

        $etag = $initial->headers->get('ETag');

        $conditional = $this
            ->withHeader('If-None-Match', (string) $etag)
            ->getJson('/api/v1/session/bootstrap');

        $conditional->assertStatus(304);
    }
}
