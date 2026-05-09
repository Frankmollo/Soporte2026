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

        if ($request->filled('categoria')) {
            $query->where('productos.categoria_id', $request->categoria);
        }

        // Orden por nombre de categoría y luego producto (catálogo coherente con “por categoría”)
        $query->leftJoin('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->orderBy('categorias.nombre')
            ->orderBy('productos.nombre')
            ->select('productos.*');

        $productos = $query->paginate(24);
        $categorias = Categoria::query()->orderBy('nombre')->get();

        return view('store.catalog', compact('productos', 'categorias', 'esMayorista'));
    }
}
