<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ActivityLog\Enums\ActivityAction;
use Modules\ActivityLog\Models\ActivityLog;
use Modules\ActivityLog\Services\ActivityLogService;
use Modules\Project\Models\Project;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class ActivityLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivityLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ActivityLogService;
    }

    public function test_log_creates_record_with_correct_fields(): void
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create(['name' => 'Acme', 'slug' => 'acme', 'owner_id' => $user->id]);

        $this->service->log(
            tenantId: $tenant->id,
            actor: $user,
            action: ActivityAction::ProjectCreated,
        );

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => $user->id,
            'action' => ActivityAction::ProjectCreated->value,
            'subject_type' => null,
            'subject_id' => null,
            'metadata' => null,
        ]);
    }

    public function test_log_creates_record_with_subject(): void
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create(['name' => 'Acme', 'slug' => 'acme2', 'owner_id' => $user->id]);
        $project = Project::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $user->id]);

        $this->service->log(
            tenantId: $tenant->id,
            actor: $user,
            action: ActivityAction::ProjectUpdated,
            subject: $project,
        );

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => $user->id,
            'action' => ActivityAction::ProjectUpdated->value,
            'subject_type' => Project::class,
            'subject_id' => $project->id,
        ]);
    }

    public function test_log_stores_metadata_as_json(): void
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create(['name' => 'Acme', 'slug' => 'acme3', 'owner_id' => $user->id]);

        $this->service->log(
            tenantId: $tenant->id,
            actor: $user,
            action: ActivityAction::TaskCompleted,
            metadata: ['task_title' => 'Ship it', 'project_name' => 'Alpha'],
        );

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id,
            'action' => ActivityAction::TaskCompleted->value,
        ]);

        $log = ActivityLog::query()->withoutGlobalScopes()->latest('id')->first();
        $this->assertSame(['task_title' => 'Ship it', 'project_name' => 'Alpha'], $log->metadata);
    }

    public function test_log_stores_null_metadata_when_empty_array_provided(): void
    {
        $user = User::factory()->create();
        $tenant = Tenants::query()->create(['name' => 'Acme', 'slug' => 'acme4', 'owner_id' => $user->id]);

        $this->service->log(
            tenantId: $tenant->id,
            actor: $user,
            action: ActivityAction::TaskCreated,
            metadata: [],
        );

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id,
            'action' => ActivityAction::TaskCreated->value,
            'metadata' => null,
        ]);
    }
}
