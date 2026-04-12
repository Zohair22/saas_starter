<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class InertiaFrontendBootstrapTest extends TestCase
{
    public function test_login_register_and_app_routes_render_inertia_root(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/register')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/app')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/app/projects')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/app/projects/create')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/app/tenants/create')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/app/projects/1')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/app/projects/1/edit')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/app/projects/1/tasks')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/app/projects/1/tasks/create')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/app/projects/1/tasks/1')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/app/projects/1/tasks/1/edit')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/app/billing')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/app/memberships')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/app/logs')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/app/audit-logs')
            ->assertRedirect('/app/logs?tab=audit');

        $this->get('/app/activity-logs')
            ->assertRedirect('/app/logs?tab=activity');
    }
}
