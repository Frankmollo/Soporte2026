<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CarritoMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existe_tabla_carrito(): void
    {
        $this->assertTrue(Schema::hasTable('carrito'));
        $this->assertTrue(Schema::hasColumns('carrito', ['usuario_id', 'producto_id', 'cantidad']));
    }
}
