<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use Illuminate\Http\Request;

class WebCheckoutController extends Controller
{
    public function process(Request $request, CheckoutService $checkout)
    {
        $request->validate([
            'nit_ci' => 'required|string|max:64',
            'razon_social' => 'required|string|max:255',
        ]);

        $usuarioId = (int) $request->session()->get('tumomito_user_id', config('tumomito.guest_user_id'));

        try {
            $result = $checkout->runFromCart(
                $usuarioId,
                $request->input('nit_ci'),
                $request->input('razon_social'),
            );
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('cart.index')
                ->with('error', $e->getMessage());
        }

        return view('store.success', [
            'pedido' => $result['pedido'],
            'factura' => $result['factura'],
        ]);
    }
}
