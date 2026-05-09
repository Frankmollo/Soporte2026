<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FacturasMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existe_tabla_facturas(): void
    {
        $this->assertTrue(Schema::hasTable('facturas'));
        $this->assertTrue(Schema::hasColumns(
            'facturas',
            ['pedido_id', 'nit_ci', 'razon_social', 'monto_total', 'fecha_emision']
        ));
    }
}
