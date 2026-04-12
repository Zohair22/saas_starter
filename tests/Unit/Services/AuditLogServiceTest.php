<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Models\AuditLog;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Project\Models\Project;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuditLogService;
    }

    public function test_record_creates_audit_log_with_correct_fields(): void
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create(['name' => 'Acme', 'slug' => 'acme', 'owner_id' => $user->id]);

        $this->service->record(
            action: AuditAction::ProjectCreated,
            tenantId: $tenant->id,
            actor: $user,
            ipAddress: '127.0.0.1',
        );

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => $user->id,
            'action' => AuditAction::ProjectCreated->value,
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_record_stores_old_and_new_values(): void
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create(['name' => 'Acme', 'slug' => 'acme2', 'owner_id' => $user->id]);

        $this->service->record(
            action: AuditAction::ProjectUpdated,
            tenantId: $tenant->id,
            actor: $user,
            oldValues: ['name' => 'Old Name'],
            newValues: ['name' => 'New Name'],
        );

        $log = AuditLog::query()->withoutGlobalScopes()->latest('id')->first();

        $this->assertSame(['name' => 'Old Name'], $log->old_values);
        $this->assertSame(['name' => 'New Name'], $log->new_values);
    }

    public function test_record_works_with_null_actor(): void
    {
        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme3',
            'owner_id' => User::factory()->create()->id,
        ]);

        $this->service->record(
            action: AuditAction::BillingInvoicePaid,
            tenantId: $tenant->id,
            actor: null,
        );

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => null,
            'action' => AuditAction::BillingInvoicePaid->value,
        ]);
    }

    public function test_record_stores_subject_type_and_id(): void
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create(['name' => 'Acme', 'slug' => 'acme4', 'owner_id' => $user->id]);
        $project = Project::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $user->id]);

        $this->service->record(
            action: AuditAction::ProjectDeleted,
            tenantId: $tenant->id,
            actor: $user,
            subject: $project,
        );

        $this->assertDatabaseHas('audit_logs', [
            'subject_type' => Project::class,
            'subject_id' => $project->id,
        ]);
    }
}
