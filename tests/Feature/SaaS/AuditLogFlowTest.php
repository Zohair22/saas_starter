<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Membership\Enums\MembershipRole;
use Tests\TestCase;

class AuditLogFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_creation_is_written_to_audit_logs(): void
    {
        [$tenant, $user] = $this->createTenantWithMember(MembershipRole::Admin);

        Sanctum::actingAs($user);

        $this->withHeaders(['X-Tenant-ID' => (string) $tenant->id])
            ->postJson('/api/v1/projects', [
                'name' => 'Audit Project',
                'description' => 'created for audit test',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => $user->id,
            'action' => 'project.created',
        ]);

        $this->withHeaders(['X-Tenant-ID' => (string) $tenant->id])
            ->getJson('/api/v1/audit-logs')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
