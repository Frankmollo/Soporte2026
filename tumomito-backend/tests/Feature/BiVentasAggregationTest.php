<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_bi_ventas_diaria_incluye_todos_los_dias_del_rango(): void
    {
        Usuario::query()->create([
            'nombre' => 'Cliente2',
            'email' => 'bi-range@example.com',
            'contrasena' => 'secret',
            'direccion' => null,
            'es_mayorista' => false,
            'rol' => 'cliente',
        ]);

        Pedido::query()->create([
            'usuario_id' => 1,
            'fecha' => Carbon::parse('2026-04-10 14:00:00'),
            'estado' => 'Procesado',
            'canal_venta' => 'web',
            'estado_pago' => 'pagado',
            'estado_logistico' => 'pendiente',
            'total' => 50.0,
        ]);

        $res = $this->withSession($this->adminHeaders())->get(
            '/erp/bi/ventas?desde=2026-04-10&hasta=2026-04-12&agrupacion=dia'
        );
        $res->assertOk();
        $res->assertViewHas('labels', fn ($labels): bool => $labels === [
            '2026-04-10',
            '2026-04-11',
            '2026-04-12',
        ]);
        $res->assertViewHas('series', fn ($series): bool => count($series) === 3
            && (float) $series[0] === 50.0
            && (float) $series[1] === 0.0
            && (float) $series[2] === 0.0);
    }
}
