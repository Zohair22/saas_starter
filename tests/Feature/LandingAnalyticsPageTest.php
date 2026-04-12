<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;
use Tests\TestCase;

class LandingAnalyticsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_analytics_page(): void
    {
        $response = $this->get('/app/analytics');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_authenticated_user_can_open_analytics_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/analytics');
        $response->assertOk();
        $response->assertSee('id="app"', false);
    }
}
