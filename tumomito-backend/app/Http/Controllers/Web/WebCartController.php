<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Carrito;
use App\Models\Producto;
use App\Services\CurrentUserService;
use App\Services\PricingService;
use Illuminate\Http\Request;

class WebCartController extends Controller
{
    public function index(Request $request, PricingService $pricingService, CurrentUserService $currentUser)
    {
        $usuarioId = $currentUser->id($request);
        $esMayorista = $pricingService->esMayorista((int) $usuarioId);
        $carrito = Carrito::with('producto')->where('usuario_id', $usuarioId)->get();

        $total = 0;
        foreach ($carrito as $item) {
            $total += $pricingService->precioUnitario($item->producto, (int) $usuarioId) * $item->cantidad;
        }

        return view('store.cart', compact('carrito', 'total', 'esMayorista'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|integer|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        $usuarioId = (int) $request->session()->get('tumomito_user_id', config('tumomito.guest_user_id'));
        $productoId = (int) $request->input('producto_id');
        $cantidad = (int) $request->input('cantidad');

        $producto = Producto::findOrFail($productoId);

        $linea = Carrito::where('usuario_id', $usuarioId)->where('producto_id', $productoId)->first();
        $cantidadEnCarrito = $linea ? (int) $linea->cantidad : 0;
        $necesaria = $cantidadEnCarrito + $cantidad;

        if ($necesaria > $producto->stock) {
            return redirect()
                ->back()
                ->with('error', "No hay stock suficiente para «{$producto->nombre}» (máximo {$producto->stock} unidades disponibles).");
        }

        if ($linea) {
            $linea->cantidad = $necesaria;
            $linea->save();
        } else {
            Carrito::create([
                'usuario_id' => $usuarioId,
                'producto_id' => $productoId,
                'cantidad' => $cantidad,
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Producto añadido al carrito.');
    }

    public function destroy(string $id)
    {
        $usuarioId = (int) request()->session()->get('tumomito_user_id', config('tumomito.guest_user_id'));
        Carrito::where('usuario_id', $usuarioId)->where('id', $id)->delete();

        return redirect()->route('cart.index')->with('success', 'Producto eliminado.');
    }
}
