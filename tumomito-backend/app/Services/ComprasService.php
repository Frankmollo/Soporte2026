<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\InventarioMovimiento;
use App\Models\LoteImportacion;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ComprasService
{
    /**
     * @param array<int,array{producto_id:int,cantidad:int,costo_unitario:float}> $items
     */
    public function registrarCompra(int $proveedorId, array $items, ?string $referencia = null): Compra
    {
        return DB::transaction(function () use ($proveedorId, $items, $referencia) {
            if (empty($items)) {
                throw new RuntimeException('La compra debe incluir al menos un producto.');
            }

            $compra = Compra::create([
                'proveedor_id' => $proveedorId,
                'fecha' => now(),
                'estado' => 'recibida',
                'referencia' => $referencia,
                'total' => 0,
            ]);

            $total = 0.0;

            foreach ($items as $item) {
                $producto = Producto::query()->lockForUpdate()->find($item['producto_id']);
                if (!$producto) {
                    throw new RuntimeException("Producto {$item['producto_id']} no encontrado.");
                }

                $cantidad = (int) $item['cantidad'];
                $costo = (float) $item['costo_unitario'];
                $subtotal = $cantidad * $costo;
                $stockAnterior = (int) $producto->stock;

                CompraDetalle::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costo,
                    'subtotal' => $subtotal,
                ]);

                $producto->stock = $stockAnterior + $cantidad;
                if (Schema::hasColumn('productos', 'precio_mayorista') && $producto->precio_mayorista === null) {
                    $producto->precio_mayorista = $producto->precio;
                }
                $producto->save();

                if (Schema::hasTable('lotes_importacion')) {
                    LoteImportacion::create([
                        'producto_id' => $producto->id,
                        'cantidad_inicial' => $cantidad,
                        'cantidad_disponible' => $cantidad,
                        'fecha_ingreso' => now(),
                        'costo_unitario' => $costo,
                    ]);
                }

                if (Schema::hasTable('inventario_movimientos')) {
                    InventarioMovimiento::create([
                        'producto_id' => $producto->id,
                        'tipo' => 'entrada',
                        'cantidad' => $cantidad,
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => (int) $producto->stock,
                        'referencia_tipo' => 'compra',
                        'referencia_id' => $compra->id,
                        'fecha' => now(),
                        'nota' => 'Ingreso por compra',
                    ]);
                }

                $total += $subtotal;
            }

            $compra->total = $total;
            $compra->save();

            return $compra->fresh('detalles');
        });
    }
}
