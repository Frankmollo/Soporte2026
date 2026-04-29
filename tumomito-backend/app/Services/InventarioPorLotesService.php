<?php

namespace App\Services;

use App\Models\DetallePedidoLote;
use App\Models\LoteImportacion;
use App\Models\Producto;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Inventario por lotes: PEPS (FIFO por fecha de ingreso) y UEPS (prioridad a lotes más recientes).
 */
class InventarioPorLotesService
{
    /** Si ya existen líneas por producto pero no tabla de lotes, no hacer nada especial. */
    public function soportePorLotesHabilitado(): bool
    {
        return Schema::hasTable('lotes_importacion') && Schema::hasTable('detalle_pedido_lotes');
    }

    /** Crea un lote de apertura con el stock vigente cuando aún no hay lotes registrados (migración histórica). */
    public function asegurarLoteDesdeStockSiSinLotes(Producto $producto): void
    {
        if (! $this->soportePorLotesHabilitado()) {
            return;
        }

        $existe = LoteImportacion::where('producto_id', $producto->id)->exists();
        if ($existe) {
            return;
        }

        if ($producto->stock <= 0) {
            return;
        }

        LoteImportacion::create([
            'producto_id' => $producto->id,
            'cantidad_inicial' => $producto->stock,
            'cantidad_disponible' => $producto->stock,
            'fecha_ingreso' => now(),
            'costo_unitario' => max(0, (float) $producto->precio),
        ]);
    }

    /**
     * Descuenta unidades desde lotes y guarda la trazabilidad en detalle_pedido_lotes.
     *
     * @throws RuntimeException
     */
    public function registrarConsumoDesdeLotes(Producto $producto, int $cantidad, int $detallePedidoId): void
    {
        if (! $this->soportePorLotesHabilitado()) {
            return;
        }

        $orden = (($producto->metodo_valoracion ?? 'PEPS') === 'UEPS') ? 'desc' : 'asc';

        $restante = $cantidad;

        /** @var \Illuminate\Database\Eloquent\Collection<int, LoteImportacion> $lotes */
        $lotes = LoteImportacion::query()
            ->where('producto_id', $producto->id)
            ->where('cantidad_disponible', '>', 0)
            ->orderBy('fecha_ingreso', $orden)
            ->orderBy('id', $orden)
            ->lockForUpdate()
            ->get();

        foreach ($lotes as $lote) {
            if ($restante <= 0) {
                break;
            }

            $usar = min($lote->cantidad_disponible, $restante);
            $lote->cantidad_disponible -= $usar;
            $lote->save();
            $restante -= $usar;

            DetallePedidoLote::create([
                'detalle_pedido_id' => $detallePedidoId,
                'lote_id' => $lote->id,
                'cantidad' => $usar,
                'costo_unitario_snapshot' => $lote->costo_unitario,
            ]);
        }

        if ($restante > 0) {
            throw new RuntimeException(
                "Saldo insuficiente en lotes para «{$producto->nombre}» (restan {$restante} unidades)."
            );
        }

        $sumaDisp = LoteImportacion::where('producto_id', $producto->id)->sum('cantidad_disponible');
        $producto->stock = (int) $sumaDisp;
        $producto->saveQuietly();
    }

    /** Descuento de stock cuando no existe el módulo de lotes (instalaciones sin migrar). */
    public function decrementarStockSimple(Producto $producto, int $cantidad): void
    {
        $producto->stock -= $cantidad;
        $producto->saveQuietly();
    }
}
