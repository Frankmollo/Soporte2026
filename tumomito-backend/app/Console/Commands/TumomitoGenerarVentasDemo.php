<?php

namespace App\Console\Commands;

use App\Models\DetallePedido;
use App\Models\Factura;
use App\Models\InventarioMovimiento;
use App\Models\Pedido;
use App\Models\Producto;
use App\Services\InventarioPorLotesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class TumomitoGenerarVentasDemo extends Command
{
    protected $signature = 'tumomito:generar-ventas-demo
                            {--pedidos=40 : Cantidad de pedidos a crear}
                            {--items-min=1 : Items mínimos por pedido}
                            {--items-max=4 : Items máximos por pedido}
                            {--dias=30 : Rango de días hacia atrás para fechas}
                            {--usuario= : usuario_id (tabla usuarios) para asignar pedidos}';

    protected $description = 'Genera pedidos/detalle/facturas demo para poblar BI (ventas/productos).';

    public function handle(InventarioPorLotesService $inventario): int
    {
        if (!Schema::hasTable('pedidos') || !Schema::hasTable('detalle_pedido') || !Schema::hasTable('productos')) {
            $this->error('Faltan tablas (pedidos/detalle_pedido/productos).');
            return self::FAILURE;
        }

        $nPedidos = max(1, (int) $this->option('pedidos'));
        $itemsMin = max(1, (int) $this->option('items-min'));
        $itemsMax = max($itemsMin, (int) $this->option('items-max'));
        $dias = max(1, (int) $this->option('dias'));
        $usuarioId = $this->option('usuario') ? (int) $this->option('usuario') : (int) config('tumomito.guest_user_id');

        $productos = Producto::query()->where('stock', '>', 0)->inRandomOrder()->limit(400)->get();
        if ($productos->isEmpty()) {
            $this->error('No hay productos con stock > 0 para simular ventas.');
            return self::FAILURE;
        }

        $creados = 0;
        $detalles = 0;

        for ($i = 0; $i < $nPedidos; $i++) {
            $ok = false;
            for ($try = 0; $try < 5; $try++) {
                try {
                    DB::transaction(function () use ($usuarioId, $itemsMin, $itemsMax, $dias, $inventario, &$creados, &$detalles, &$ok) {
                $fecha = now()->subDays(random_int(0, $dias - 1))->setTime(random_int(8, 20), random_int(0, 59), 0);

                $pedidoData = [
                    'usuario_id' => $usuarioId,
                    'fecha' => $fecha,
                    'estado' => 'Procesado',
                    'total' => 0,
                ];
                if (Schema::hasColumn('pedidos', 'canal_venta')) $pedidoData['canal_venta'] = 'web';
                if (Schema::hasColumn('pedidos', 'estado_pago')) $pedidoData['estado_pago'] = 'pagado';
                if (Schema::hasColumn('pedidos', 'estado_logistico')) $pedidoData['estado_logistico'] = 'entregado';

                $pedido = Pedido::create($pedidoData);

                $nItems = random_int($itemsMin, $itemsMax);
                $total = 0.0;

                // Selección de productos con stock en el momento de la transacción
                $seleccion = Producto::query()
                    ->where('stock', '>', 0)
                    ->inRandomOrder()
                    ->limit($nItems)
                    ->get();

                foreach ($seleccion as $prod) {
                    $p = Producto::query()->lockForUpdate()->find($prod->id);
                    if (!$p) continue;
                    if ((int) $p->stock <= 0) continue;

                    $cantidad = min((int) $p->stock, random_int(1, 4));
                    if ($cantidad <= 0) continue;

                    $pu = (float) $p->precio;
                    $sub = $pu * $cantidad;

                    $detalle = DetallePedido::create([
                        'pedido_id' => $pedido->id,
                        'producto_id' => $p->id,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $pu,
                        'subtotal' => $sub,
                    ]);

                    // Descarga inventario por lotes si está habilitado; sino stock simple.
                    if ($inventario->soportePorLotesHabilitado()) {
                        $inventario->asegurarLoteDesdeStockSiSinLotes($p);
                        $p->refresh();
                        if ((int) $p->stock < $cantidad) {
                            DetallePedido::query()->whereKey($detalle->id)->delete();

                            continue;
                        }
                        $stockAntes = (int) $p->stock;
                        $inventario->registrarConsumoDesdeLotes($p, $cantidad, $detalle->id);
                        $p->refresh();
                        $stockNuevo = (int) $p->stock;
                    } else {
                        $stockAntes = (int) $p->stock;
                        $p->stock -= $cantidad;
                        $p->saveQuietly();
                        $p->refresh();
                        $stockNuevo = (int) $p->stock;
                    }

                    if (Schema::hasTable('inventario_movimientos')) {
                        InventarioMovimiento::create([
                            'producto_id' => $p->id,
                            'tipo' => 'salida',
                            'cantidad' => $cantidad,
                            'stock_anterior' => $stockAntes,
                            'stock_nuevo' => $stockNuevo,
                            'referencia_tipo' => 'pedido',
                            'referencia_id' => $pedido->id,
                            'fecha' => $fecha,
                            'nota' => 'Salida demo (tumomito:generar-ventas-demo)',
                        ]);
                    }

                    $total += $sub;
                    $detalles++;
                }

                if ($total <= 0) {
                    // Si no se pudo cargar nada, borrar el pedido
                    $pedido->delete();
                    throw new RuntimeException('Pedido demo sin items (reintento).');
                }

                $pedido->total = $total;
                $pedido->saveQuietly();

                if (Schema::hasTable('facturas')) {
                    Factura::create([
                        'pedido_id' => $pedido->id,
                        'nit_ci' => '0000000',
                        'razon_social' => 'DEMO',
                        'monto_total' => $total,
                        'fecha_emision' => now(),
                    ]);
                }

                $creados++;
                $ok = true;
                    });
                } catch (RuntimeException $e) {
                    // reintenta
                    continue;
                }
                if ($ok) {
                    break;
                }
            }
        }

        $this->info("Ventas demo generadas. Pedidos: {$creados} | Detalles: {$detalles}");
        $this->line('Ahora recarga: /erp/bi/productos');

        return self::SUCCESS;
    }
}

