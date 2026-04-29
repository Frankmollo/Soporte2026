<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use App\Services\CurrentUserService;
use App\Services\PricingService;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request, PricingService $pricingService, CurrentUserService $currentUser)
    {
        $usuarioId = $currentUser->id($request);
        $esMayorista = $pricingService->esMayorista($usuarioId);
        $query = Producto::with('categoria')->catalogCompatibility();

        if ($request->has('categoria') && $request->categoria != '') {
            $query->where('categoria_id', $request->categoria);
        }

        $productos = $query->paginate(24);
        $categorias = Categoria::all();

        return view('store.catalog', compact('productos', 'categorias', 'esMayorista'));
    }
}
