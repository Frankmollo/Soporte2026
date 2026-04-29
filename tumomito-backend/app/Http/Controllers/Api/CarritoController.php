<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrito;
use App\Models\Producto;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarritoController extends Controller
{
    public function index(Request $request, PricingService $pricingService): JsonResponse
    {
        $usuarioId = (int) $request->query('usuario_id', config('tumomito.guest_user_id'));
        $carrito = Carrito::with('producto')->where('usuario_id', $usuarioId)->get();
        $esMayorista = $pricingService->esMayorista($usuarioId);

        $payload = $carrito->map(function (Carrito $linea) use ($esMayorista) {
            $precio = $linea->producto->precioParaCliente($esMayorista);

            return [
                'id' => $linea->id,
                'usuario_id' => $linea->usuario_id,
                'producto_id' => $linea->producto_id,
                'cantidad' => $linea->cantidad,
                'precio_unitario' => $precio,
                'subtotal' => $precio * $linea->cantidad,
                'producto' => $linea->producto,
            ];
        });

        return response()->json($payload);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'usuario_id' => 'required|integer|min:1',
            'producto_id' => 'required|integer',
            'cantidad' => 'required|integer|min:1',
        ]);

        $producto = Producto::find($data['producto_id']);
        if ($producto === null) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        $linea = Carrito::where('usuario_id', $data['usuario_id'])
            ->where('producto_id', $data['producto_id'])
            ->first();

        $enCarrito = $linea ? (int) $linea->cantidad : 0;
        $necesaria = $enCarrito + (int) $data['cantidad'];

        if ($necesaria > $producto->stock) {
            return response()->json([
                'error' => 'Stock insuficiente',
                'disponible' => $producto->stock,
                'solicitado_total' => $necesaria,
            ], 422);
        }

        if ($linea) {
            $linea->cantidad = $necesaria;
            $linea->save();
        } else {
            $linea = Carrito::create([
                'usuario_id' => $data['usuario_id'],
                'producto_id' => $data['producto_id'],
                'cantidad' => $data['cantidad'],
            ]);
        }

        return response()->json(['message' => 'Producto añadido al carrito', 'item' => $linea]);
    }
}
