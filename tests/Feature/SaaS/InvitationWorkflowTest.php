<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Invitation;
use Modules\Membership\Models\Membership;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class InvitationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_active_invitations(): void
    {
        [$admin, $tenant] = $this->createTenantActor(MembershipRole::Admin->value);

        Invitation::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'active@example.com',
            'role' => MembershipRole::Member->value,
            'token' => str_repeat('c', 64),
            'invited_by' => $admin->id,
            'expires_at' => now()->addDays(2),
        ]);

        Invitation::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'accepted@example.com',
            'role' => MembershipRole::Member->value,
            'token' => str_repeat('d', 64),
            'invited_by' => $admin->id,
            'expires_at' => now()->addDays(2),
            'accepted_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/v1/invitations');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.email', 'active@example.com');
    }

    public function test_member_cannot_list_invitations(): void
    {
        [$member, $tenant] = $this->createTenantActor(MembershipRole::Member->value);

        Sanctum::actingAs($member);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/v1/invitations');

        $response->assertForbidden();
    }

    public function test_admin_can_create_invitation(): void
    {
        [$admin, $tenant] = $this->createTenantActor(MembershipRole::Admin->value);

        Sanctum::actingAs($admin);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/invitations', [
                'email' => 'new.member@example.com',
                'role' => MembershipRole::Member->value,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('invitations', [
            'tenant_id' => $tenant->id,
            'email' => 'new.member@example.com',
            'role' => MembershipRole::Member->value,
        ]);
    }

    public function test_member_cannot_create_invitation(): void
    {
        [$member, $tenant] = $this->createTenantActor(MembershipRole::Member->value);

        Sanctum::actingAs($member);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/invitations', [
                'email' => 'new.member@example.com',
                'role' => MembershipRole::Member->value,
            ]);

        $response->assertForbidden();
    }

    public function test_duplicate_active_invitation_is_rejected(): void
    {
        [$owner, $tenant] = $this->createTenantActor(MembershipRole::Owner->value);

        Invitation::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'existing@example.com',
            'role' => MembershipRole::Member->value,
            'token' => str_repeat('a', 64),
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(2),
        ]);

        Sanctum::actingAs($owner);

        $response = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/invitations', [
                'email' => 'existing@example.com',
                'role' => MembershipRole::Member->value,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_accept_invitation_creates_user_and_membership(): void
    {
        [$owner, $tenant] = $this->createTenantActor(MembershipRole::Owner->value);

        $invitation = Invitation::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'invited@example.com',
            'role' => MembershipRole::Member->value,
            'token' => str_repeat('b', 64),
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(2),
        ]);

        $response = $this->postJson('/api/v1/invitations/'.$invitation->token.'/accept', [
            'name' => 'Invited User',
            'password' => 'password123',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'email' => 'invited@example.com',
        ]);

        $invitedUser = User::query()->where('email', 'invited@example.com')->firstOrFail();

        $this->assertDatabaseHas('memberships', [
            'tenant_id' => $tenant->id,
            'user_id' => $invitedUser->id,
            'role' => MembershipRole::Member->value,
        ]);

        $this->assertNotNull($invitation->refresh()->accepted_at);
    }

    public function test_accepting_invalid_invitation_token_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/invitations/invalid-token/accept', [
            'name' => 'Invited User',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['token']);
    }

    /**
     * @return array{0: User, 1: Tenants}
     */
    private function createTenantActor(string $role): array
    {
        $actor = User::factory()->create();

        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme-'.str()->random(5),
            'owner_id' => $actor->id,
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $actor->id,
            'role' => $role,
        ]);

        return [$actor, $tenant];
    }
}
