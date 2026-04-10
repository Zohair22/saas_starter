<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class TenantAwareAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_non_member_cannot_access_tenant_protected_route(): void
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme-'.str()->random(5),
            'owner_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/v1/memberships');

        $response->assertForbidden();
    }

    public function test_authenticated_member_can_access_tenant_protected_route(): void
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme-'.str()->random(5),
            'owner_id' => $user->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Member->value,
        ]);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/v1/memberships');

        $response->assertOk();
    }
}
