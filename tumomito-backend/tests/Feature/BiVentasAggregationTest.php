<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiVentasAggregationTest extends TestCase
{
    use RefreshDatabase;

    private function adminHeaders(): array
    {
        return [
            'tumomito_user_id' => 1,
            'tumomito_user_name' => 'Admin BI',
            'tumomito_user_role' => 'admin',
        ];
    }

    public function test_bi_ventas_dia_semana_mes_sin_error_sql(): void
    {
        Usuario::query()->create([
            'nombre' => 'Cliente',
            'email' => 'bi-test@example.com',
            'contrasena' => 'secret',
            'direccion' => null,
            'es_mayorista' => false,
            'rol' => 'cliente',
        ]);

        Pedido::query()->create([
            'usuario_id' => 1,
            'fecha' => now(),
            'estado' => 'Procesado',
            'canal_venta' => 'web',
            'estado_pago' => 'pagado',
            'estado_logistico' => 'pendiente',
            'total' => 99.5,
        ]);

        $s = $this->adminHeaders();
        foreach (['dia', 'semana', 'mes'] as $agr) {
            $res = $this->withSession($s)->get('/erp/bi/ventas?agrupacion='.$agr);
            $res->assertOk();
            $this->assertStringNotContainsString('Undefined function', (string) $res->content());
        }
    }
}
