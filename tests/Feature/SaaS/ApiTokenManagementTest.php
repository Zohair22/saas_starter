<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\User\Models\User;
use Tests\TestCase;

class ApiTokenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_personal_api_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/tokens', [
            'name' => 'mobile-device',
            'abilities' => ['projects:read'],
        ]);

        $response->assertCreated()->assertJsonStructure(['message', 'token']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'mobile-device',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'api_token.created',
        ]);
    }

    public function test_user_can_revoke_own_token(): void
    {
        $user = User::factory()->create();
        $issuedToken = $user->createToken('temp-token');

        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/tokens/'.$issuedToken->accessToken->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $issuedToken->accessToken->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'api_token.revoked',
        ]);
    }
}
