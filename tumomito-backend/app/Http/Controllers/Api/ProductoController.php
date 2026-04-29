<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use App\Services\PricingService;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index(Request $request, PricingService $pricingService): JsonResponse
    {
        $usuarioId = (int) $request->query('usuario_id', config('tumomito.guest_user_id'));
        $productos = Producto::with('categoria')->catalogCompatibility()->get();
        $esMayorista = $pricingService->esMayorista($usuarioId);

        $payload = $productos->map(function (Producto $producto) use ($esMayorista) {
            return [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'precio' => $producto->precio,
                'precio_mayorista' => $producto->precio_mayorista ?? null,
                'precio_cliente' => $producto->precioParaCliente($esMayorista),
                'stock' => $producto->stock,
                'metodo_valoracion' => $producto->metodo_valoracion ?? null,
                'categoria' => $producto->categoria,
            ];
        });

        return response()->json($payload);
    }
}
