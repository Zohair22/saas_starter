<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\User\Models\User;
use Tests\TestCase;

class ProfileSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile_name_and_email(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/profile', [
            'name' => 'Updated Name',
            'email' => 'new@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.email', 'new@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_user_can_update_password_with_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/profile/password', [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertOk();

        $this->assertTrue(password_verify('new-password-123', $user->fresh()->password));
    }

    public function test_user_can_delete_account_with_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/profile', [
            'password' => 'password',
        ]);

        $response->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
