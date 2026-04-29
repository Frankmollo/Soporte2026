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
}
