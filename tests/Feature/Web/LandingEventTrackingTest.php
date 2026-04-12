<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingEventTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_track_landing_cta_event(): void
    {
        $token = 'test-token';

        $response = $this
            ->withSession(['_token' => $token])
            ->post('/track/landing-event', [
                '_token' => $token,
                'page' => 'welcome',
                'cta_id' => 'hero-primary',
                'ab_variant' => 'b',
                'path' => '/?ab=b',
                'referrer' => 'https://example.test/',
            ]);

        $response->assertNoContent();

        $this->assertDatabaseHas('landing_events', [
            'page' => 'welcome',
            'cta_id' => 'hero-primary',
            'ab_variant' => 'b',
            'path' => '/?ab=b',
        ]);
    }

    public function test_tracking_requires_cta_id(): void
    {
        $token = 'test-token';

        $response = $this
            ->withSession(['_token' => $token])
            ->post('/track/landing-event', [
                '_token' => $token,
                'page' => 'welcome',
            ]);

        $response->assertSessionHasErrors(['cta_id']);
        $this->assertDatabaseCount('landing_events', 0);
    }
}
