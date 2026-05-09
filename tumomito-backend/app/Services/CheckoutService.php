<?php

namespace App\Services;

use App\Models\Carrito;
use App\Models\DetallePedido;
use App\Models\Factura;
use App\Models\InventarioMovimiento;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CheckoutService
{
    /** Estado único tras completar cobro/checkout en pantalla */
    public const PEDIDO_ESTADO_FINAL = 'Procesado';

    public function __construct(
        protected InventarioPorLotesService $inventarioPorLotes,
        protected PricingService $pricingService,
    ) {}

    /**
     * Procesa el carrito: pedido, detalle, factura, descarga de inventario con PEPS/UEPS por lotes (si están migradas las tablas).
     *
     * @return array{pedido: Pedido, factura: Factura}
     */
    public function runFromCart(int $usuarioId, string $nitCi, string $razonSocial): array
    {
        return DB::transaction(function () use ($usuarioId, $nitCi, $razonSocial) {
            $items = Carrito::with('producto')
                ->where('usuario_id', $usuarioId)
                ->orderBy('id')
                ->get();

            if ($items->isEmpty()) {
                throw new RuntimeException('El carrito está vacío.');
            }

            foreach ($items as $item) {
                $prod = Producto::whereKey($item->producto_id)->lockForUpdate()->first();
                if ($prod === null) {
                    throw new RuntimeException("Producto #{$item->producto_id} no encontrado.");
                }

                $this->inventarioPorLotes->asegurarLoteDesdeStockSiSinLotes($prod);
                $prod->refresh();

                if ($prod->stock < $item->cantidad) {
                    throw new RuntimeException(
                        "Stock insuficiente para «{$prod->nombre}» (solicitado: {$item->cantidad}, disponible: {$prod->stock})."
                    );
                }
            }

            $total = 0.0;
            foreach ($items as $item) {
                $total += $this->pricingService->precioUnitario($item->producto, $usuarioId) * (int) $item->cantidad;
            }

            $pedidoData = [
                'usuario_id' => $usuarioId,
                'estado' => self::PEDIDO_ESTADO_FINAL,
                'total' => $total,
            ];
            if (Schema::hasColumn('pedidos', 'canal_venta')) {
                $pedidoData['canal_venta'] = 'web';
            }
            if (Schema::hasColumn('pedidos', 'estado_pago')) {
                $pedidoData['estado_pago'] = 'pagado';
            }
            if (Schema::hasColumn('pedidos', 'estado_logistico')) {
                $pedidoData['estado_logistico'] = 'pendiente';
            }

            $pedido = Pedido::create($pedidoData);

            foreach ($items as $item) {
                $producto = Producto::whereKey($item->producto_id)->lockForUpdate()->firstOrFail();

                $this->inventarioPorLotes->asegurarLoteDesdeStockSiSinLotes($producto);
                $producto->refresh();

                $precioUnitario = $this->pricingService->precioUnitario($producto, $usuarioId);
                $detalle = DetallePedido::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $item->producto_id,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $precioUnitario * $item->cantidad,
                ]);

                $stockAnterior = (int) $producto->stock;
                if ($this->inventarioPorLotes->soportePorLotesHabilitado()) {
                    $this->inventarioPorLotes->registrarConsumoDesdeLotes(
                        $producto,
                        $item->cantidad,
                        $detalle->id
                    );
                } else {
                    $this->inventarioPorLotes->decrementarStockSimple($producto, $item->cantidad);
                    $producto->refresh();
                }

                if (Schema::hasTable('inventario_movimientos')) {
                    InventarioMovimiento::create([
                        'producto_id' => $producto->id,
                        'tipo' => 'salida',
                        'cantidad' => (int) $item->cantidad,
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => (int) $producto->stock,
                        'referencia_tipo' => 'pedido',
                        'referencia_id' => $pedido->id,
                        'fecha' => now(),
                        'nota' => 'Salida por checkout web',
                    ]);
                }
            }

            $factura = Factura::create([
                'pedido_id' => $pedido->id,
                'nit_ci' => $nitCi,
                'razon_social' => $razonSocial,
                'monto_total' => $total,
                'fecha_emision' => now(),
            ]);

            Carrito::where('usuario_id', $usuarioId)->delete();

            return [
                'pedido' => $pedido->fresh(),
                'factura' => $factura->fresh(),
            ];
        });
    }
}
