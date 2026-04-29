<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function process(Request $request, CheckoutService $checkout): JsonResponse
    {
        $data = $request->validate([
            'usuario_id' => 'sometimes|integer|min:1',
            'nit_ci' => 'nullable|string|max:64',
            'razon_social' => 'nullable|string|max:255',
        ]);

        $usuarioId = $data['usuario_id'] ?? config('tumomito.guest_user_id');
        $nitCi = $data['nit_ci'] ?? '0000000000';
        $razonSocial = $data['razon_social'] ?? 'Cliente';

        try {
            $result = $checkout->runFromCart($usuarioId, $nitCi, $razonSocial);
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'vacío') ? 400 : 422;

            return response()->json(['error' => $e->getMessage()], $code);
        }

        return response()->json([
            'message' => 'Checkout completado exitosamente',
            'pedido_id' => $result['pedido']->id,
            'total' => $result['pedido']->total,
            'estado_logistico' => $result['pedido']->estado_logistico ?? null,
        ]);
    }
}
