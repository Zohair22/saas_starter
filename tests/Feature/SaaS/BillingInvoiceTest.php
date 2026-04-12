<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Membership\Enums\MembershipRole;
use Modules\User\Models\User;
use Tests\TestCase;

class BillingInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_invoices_endpoint(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Member);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/v1/billing/invoices');

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_invoices_returns_empty_when_no_stripe_id(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Owner);

        $this->assertNull($tenant->stripe_id);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/v1/billing/invoices');

        $response->assertOk();
        $response->assertExactJson(['data' => []]);
    }

    public function test_user_outside_tenant_cannot_access_invoices(): void
    {
        [$tenant] = $this->createTenantWithMember(MembershipRole::Owner);

        $outsider = User::factory()->create();
        Sanctum::actingAs($outsider);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/v1/billing/invoices');

        $response->assertForbidden();
    }
}
