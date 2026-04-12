<?php

namespace Tests\Feature\Web;

use App\Models\LandingEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;
use Tests\TestCase;

class LandingEventReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_endpoint_requires_authentication(): void
    {
        $response = $this->get('/track/landing-report');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_aggregated_report(): void
    {
        $user = User::factory()->create();

        LandingEvent::query()->create([
            'page' => 'welcome',
            'cta_id' => 'hero-primary',
            'ab_variant' => 'a',
            'path' => '/?ab=a',
            'referrer' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test-agent',
            'created_at' => now()->subDays(1),
        ]);

        LandingEvent::query()->create([
            'page' => 'welcome',
            'cta_id' => 'hero-primary',
            'ab_variant' => 'a',
            'path' => '/?ab=a',
            'referrer' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test-agent',
            'created_at' => now()->subDays(2),
        ]);

        LandingEvent::query()->create([
            'page' => 'welcome',
            'cta_id' => 'final-primary',
            'ab_variant' => 'b',
            'path' => '/?ab=b',
            'referrer' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test-agent',
            'created_at' => now()->subDays(1),
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/track/landing-report?days=30');

        $response->assertOk();
        $response->assertJsonPath('range_days', 30);
        $response->assertJsonPath('totals.events', 3);
        $response->assertJsonPath('totals.unique_ctas', 2);
        $response->assertJsonStructure([
            'range_days',
            'totals' => ['events', 'unique_ctas'],
            'by_variant',
            'by_cta',
            'daily',
        ]);
    }
}
