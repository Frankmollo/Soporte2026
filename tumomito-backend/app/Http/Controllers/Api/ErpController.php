<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\InventarioMovimiento;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\ComprasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ErpController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $ventasHoy = Schema::hasTable('pedidos')
            ? Pedido::query()->whereDate('fecha', now()->toDateString())->sum('total')
            : 0;
        $pedidosPendientes = Schema::hasTable('pedidos') && Schema::hasColumn('pedidos', 'estado_logistico')
            ? Pedido::query()->where('estado_logistico', 'pendiente')->count()
            : 0;

        $alertasStockBajo = 0;
        if (Schema::hasTable('productos') && Schema::hasColumn('productos', 'stock_minimo')) {
            $alertasStockBajo = Producto::query()->whereColumn('stock', '<=', 'stock_minimo')->count();
        }

        return response()->json([
            'ventas_hoy' => (float) $ventasHoy,
            'pedidos_pendientes_logistica' => $pedidosPendientes,
            'alertas_stock_bajo' => $alertasStockBajo,
            'productos_totales' => Schema::hasTable('productos') ? Producto::query()->count() : 0,
        ]);
    }

    public function proveedores(): JsonResponse
    {
        if (!Schema::hasTable('proveedores')) {
            return response()->json([]);
        }
        return response()->json(Proveedor::query()->orderBy('nombre')->get());
    }

    public function crearProveedor(Request $request): JsonResponse
    {
        if (!Schema::hasTable('proveedores')) {
            return response()->json(['error' => 'Módulo de proveedores no migrado'], 422);
        }
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'contacto' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
        ]);

        $proveedor = Proveedor::create($data);

        return response()->json($proveedor, 201);
    }

    public function registrarCompra(Request $request, ComprasService $comprasService): JsonResponse
    {
        if (!Schema::hasTable('compras')) {
            return response()->json(['error' => 'Módulo de compras no migrado'], 422);
        }
        $data = $request->validate([
            'proveedor_id' => 'required|integer|exists:proveedores,id',
            'referencia' => 'nullable|string|max:120',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|integer|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.costo_unitario' => 'required|numeric|min:0',
        ]);

        $compra = $comprasService->registrarCompra(
            (int) $data['proveedor_id'],
            $data['items'],
            $data['referencia'] ?? null
        );

        return response()->json($compra, 201);
    }

    public function compras(): JsonResponse
    {
        if (!Schema::hasTable('compras')) {
            return response()->json([]);
        }
        return response()->json(
            Compra::query()->with(['proveedor', 'detalles'])->orderByDesc('id')->limit(50)->get()
        );
    }

    public function movimientosInventario(): JsonResponse
    {
        if (!Schema::hasTable('inventario_movimientos')) {
            return response()->json([]);
        }

        return response()->json(
            InventarioMovimiento::query()->orderByDesc('fecha')->limit(200)->get()
        );
    }
}
