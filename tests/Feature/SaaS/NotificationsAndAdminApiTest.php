<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
use Modules\Project\Events\ProjectCreated;
use Modules\Project\Models\Project;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Modules\User\Notifications\WorkspaceEventNotification;
use Tests\TestCase;

class NotificationsAndAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_index_lists_database_notifications(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $user->notify(new WorkspaceEventNotification([
            'category' => 'test',
            'title' => 'Hello',
            'body' => 'World',
        ]));

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.0.data.title', 'Hello');
    }

    public function test_project_created_event_produces_member_notification(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $tenant = Tenants::query()->create([
            'name' => 'Notify Tenant',
            'slug' => 'notify-tenant',
            'owner_id' => $owner->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::Owner->value,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $member->id,
            'role' => MembershipRole::Member->value,
        ]);

        $project = Project::query()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $owner->id,
            'name' => 'Roadmap',
            'description' => 'Q2 roadmap',
        ]);

        event(new ProjectCreated($project, $owner));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $member->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_super_admin_can_access_dashboard_and_prepare_impersonation_session(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $target = User::factory()->create();

        Sanctum::actingAs($admin);

        $dashboard = $this->getJson('/api/v1/admin/dashboard');
        $dashboard->assertOk()->assertJsonStructure(['metrics' => ['tenants', 'users']]);

        $impersonate = $this->postJson("/api/v1/admin/impersonate/{$target->id}");

        $impersonate->assertOk()
            ->assertJsonPath('impersonated_user.id', $target->id)
            ->assertJsonMissingPath('token')
            ->assertJsonStructure(['auth' => ['token', 'tenant_id']]);
    }

    public function test_non_super_admin_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
    }
}
