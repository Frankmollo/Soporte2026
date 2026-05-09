<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthAndPingTest extends TestCase
{
    public function test_ping_responde_ok(): void
    {
        $response = $this->get('/ping');

        $response->assertOk()->assertSee('pong', false);
    }

    public function test_health_up_responde_ok(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
    }

    public function test_login_y_registro_get_responden_ok(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/registro')->assertOk();
    }
}
