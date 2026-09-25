<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoCredentialsVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_login_shows_the_demo_account_block(): void
    {
        $this->app->instance('env', 'local');

        $response = $this->get('/login');

        $response->assertOk()
            ->assertSee('<details class="demo-accounts">', false)
            ->assertSee('user_test@khaisaeng.test')
            ->assertSee('admin_test@khaisaeng.test')
            ->assertSee('sbadmin_test@khaisaeng.test')
            ->assertSee('<code>password</code>', false);
    }

    public function test_production_login_hides_demo_credentials_after_the_view_was_compiled_locally(): void
    {
        $this->app->instance('env', 'local');
        $this->get('/login')->assertOk()->assertSee('user_test@khaisaeng.test');

        $this->app->instance('env', 'production');

        $response = $this->get('/login');

        $response->assertOk()
            ->assertDontSee('user_test@khaisaeng.test')
            ->assertDontSee('admin_test@khaisaeng.test')
            ->assertDontSee('sbadmin_test@khaisaeng.test')
            ->assertDontSee('<code>password</code>', false)
            ->assertSee('Google')
            ->assertSee('Facebook')
            ->assertSee('LINE');
    }
}
