<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\InventarioMovimiento;
use App\Models\MarketingKpiDiario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\ComprasService;
use App\Support\DbDateAgg;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ErpWebController extends Controller
{
    public function dashboard(): View
    {
        $ventasHoy = Schema::hasTable('pedidos')
            ? (float) Pedido::query()->whereDate('fecha', now()->toDateString())->sum('total')
            : 0.0;
        $pendientes = Schema::hasTable('pedidos') && Schema::hasColumn('pedidos', 'estado_logistico')
            ? Pedido::query()->where('estado_logistico', 'pendiente')->count()
            : 0;
        $stockBajo = Schema::hasTable('productos') && Schema::hasColumn('productos', 'stock_minimo')
            ? Producto::query()->whereColumn('stock', '<=', 'stock_minimo')->count()
            : 0;
        $ultimosPedidos = Schema::hasTable('pedidos')
            ? Pedido::query()->orderByDesc('id')->limit(10)->get()
            : collect();

        // Gráfico: ventas últimos 14 días
        $ventasLabels = [];
        $ventasSeries = [];
        if (Schema::hasTable('pedidos')) {
            $desde = now()->subDays(13)->startOfDay();
            $hasta = now()->endOfDay();
            $dayExpr = DbDateAgg::exprDay('fecha');
            $rows = DB::table('pedidos')
                ->whereBetween('fecha', [$desde, $hasta])
                ->selectRaw("{$dayExpr} as k, SUM(total) as v")
                ->groupByRaw($dayExpr)
                ->orderByRaw($dayExpr)
                ->get()
                ->keyBy('k');

            for ($d = 0; $d < 14; $d++) {
                $day = $desde->copy()->addDays($d)->toDateString();
                $ventasLabels[] = $day;
                $ventasSeries[] = (float) ($rows[$day]->v ?? 0);
            }
        }

        // Gráfico: movimientos inventario (entrada vs salida) últimos 14 días
        $movLabels = $ventasLabels;
        $movEntradas = array_fill(0, count($movLabels), 0);
        $movSalidas = array_fill(0, count($movLabels), 0);
        if (Schema::hasTable('inventario_movimientos')) {
            $desde = now()->subDays(13)->startOfDay();
            $hasta = now()->endOfDay();
            $dayExprMov = DbDateAgg::exprDay('fecha');
            $rows = DB::table('inventario_movimientos')
                ->whereBetween('fecha', [$desde, $hasta])
                ->selectRaw("{$dayExprMov} as k, tipo, SUM(cantidad) as c")
                ->groupByRaw("{$dayExprMov}, tipo")
                ->get();

            $map = [];
            foreach ($rows as $r) {
                $map[$r->k][$r->tipo] = (int) $r->c;
            }
            foreach ($movLabels as $i => $day) {
                $movEntradas[$i] = (int) ($map[$day]['entrada'] ?? 0);
                $movSalidas[$i] = (int) ($map[$day]['salida'] ?? 0);
            }
        }

        return view('erp.dashboard', compact(
            'ventasHoy',
            'pendientes',
            'stockBajo',
            'ultimosPedidos',
            'ventasLabels',
            'ventasSeries',
            'movLabels',
            'movEntradas',
            'movSalidas',
        ));
    }

    public function compras(): View
    {
        $proveedores = Schema::hasTable('proveedores') ? Proveedor::query()->orderBy('nombre')->get() : collect();
        $productos = Schema::hasTable('productos') ? Producto::query()->orderBy('nombre')->limit(300)->get() : collect();
        $compras = Schema::hasTable('compras')
            ? Compra::query()->with(['proveedor', 'detalles'])->orderByDesc('id')->limit(20)->get()
            : collect();

        // Gráfico (torta): compras últimos 30 días agrupadas por semana (más legible que 30 porciones)
        $comprasWeekLabels = [];
        $comprasWeekSeries = [];
        if (Schema::hasTable('compras')) {
            $desde = now()->subDays(29)->startOfDay();
            $hasta = now()->endOfDay();
            $weekExpr = DbDateAgg::exprIsoWeek('fecha');
            $rows = DB::table('compras')
                ->whereBetween('fecha', [$desde, $hasta])
                ->selectRaw("{$weekExpr} as k, SUM(total) as v")
                ->groupByRaw($weekExpr)
                ->orderByRaw($weekExpr)
                ->get();

            $comprasWeekLabels = $rows->pluck('k')->all();
            $comprasWeekSeries = $rows->pluck('v')->map(fn ($x) => (float) $x)->all();
        }

        // Gráfico: top proveedores por gasto (30 días)
        $topProvLabels = [];
        $topProvSeries = [];
        if (Schema::hasTable('compras') && Schema::hasTable('proveedores')) {
            $desde = now()->subDays(29)->startOfDay();
            $hasta = now()->endOfDay();
            $rows = DB::table('compras as c')
                ->join('proveedores as pr', 'pr.id', '=', 'c.proveedor_id')
                ->whereBetween('c.fecha', [$desde, $hasta])
                ->selectRaw('pr.nombre as n, SUM(c.total) as v')
                ->groupByRaw('pr.nombre')
                ->orderByDesc('v')
                ->limit(8)
                ->get();

            $topProvLabels = $rows->pluck('n')->all();
            $topProvSeries = $rows->pluck('v')->map(fn ($x) => (float) $x)->all();
        }

        return view('erp.compras', compact(
            'proveedores',
            'productos',
            'compras',
            'comprasWeekLabels',
            'comprasWeekSeries',
            'topProvLabels',
            'topProvSeries',
        ));
    }

    public function crearProveedor(Request $request): RedirectResponse
    {
        if (!Schema::hasTable('proveedores')) {
            return back()->with('error', 'Módulo de proveedores no migrado. Ejecuta php artisan migrate');
        }

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'contacto' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
        ]);

        Proveedor::create($data);

        return back()->with('success', 'Proveedor creado.');
    }

    public function registrarCompra(Request $request, ComprasService $comprasService): RedirectResponse
    {
        if (!Schema::hasTable('compras')) {
            return back()->with('error', 'Módulo de compras no migrado. Ejecuta php artisan migrate');
        }

        $data = $request->validate([
            'proveedor_id' => 'required|integer|exists:proveedores,id',
            'referencia' => 'nullable|string|max:120',
            'producto_id' => 'required|array|min:1',
            'producto_id.*' => 'required|integer|exists:productos,id',
            'cantidad' => 'required|array|min:1',
            'cantidad.*' => 'required|integer|min:1',
            'costo_unitario' => 'required|array|min:1',
            'costo_unitario.*' => 'required|numeric|min:0',
        ]);

        $items = [];
        foreach ($data['producto_id'] as $i => $productoId) {
            $items[] = [
                'producto_id' => (int) $productoId,
                'cantidad' => (int) $data['cantidad'][$i],
                'costo_unitario' => (float) $data['costo_unitario'][$i],
            ];
        }

        $comprasService->registrarCompra((int) $data['proveedor_id'], $items, $data['referencia'] ?? null);

        return back()->with('success', 'Compra registrada y stock actualizado.');
    }

    public function marketing(): View
    {
        $rows = Schema::hasTable('marketing_kpis_diarios')
            ? MarketingKpiDiario::query()->orderByDesc('fecha')->limit(30)->get()
            : collect();

        $mLabels = [];
        $mIngresos = [];
        $mInversion = [];
        $mRoas = [];
        if (!$rows->isEmpty()) {
            $ordered = $rows->sortBy('fecha')->values();
            $mLabels = $ordered->pluck('fecha')->all();
            $mIngresos = $ordered->pluck('ingresos')->map(fn ($x) => (float) $x)->all();
            $mInversion = $ordered->pluck('inversion')->map(fn ($x) => (float) $x)->all();
            $mRoas = $ordered->pluck('roas')->map(fn ($x) => $x === null ? null : (float) $x)->all();
        }

        return view('erp.marketing', compact('rows', 'mLabels', 'mIngresos', 'mInversion', 'mRoas'));
    }

    public function guardarMarketing(Request $request): RedirectResponse
    {
        if (!Schema::hasTable('marketing_kpis_diarios')) {
            return back()->with('error', 'Módulo de marketing no migrado. Ejecuta php artisan migrate');
        }

        $data = $request->validate([
            'fecha' => 'required|date',
            'canal' => 'required|string|max:40',
            'inversion' => 'required|numeric|min:0',
            'visitas' => 'required|integer|min:0',
            'leads' => 'required|integer|min:0',
            'ventas' => 'required|integer|min:0',
            'ingresos' => 'required|numeric|min:0',
            'recompras' => 'nullable|integer|min:0',
            'abandono_carrito' => 'nullable|numeric|min:0|max:1',
        ]);

        $visitas = max(1, (int) $data['visitas']);
        $ventas = max(1, (int) $data['ventas']);
        $inversion = (float) $data['inversion'];
        $ingresos = (float) $data['ingresos'];
        $leads = (int) $data['leads'];

        MarketingKpiDiario::query()->updateOrCreate(
            ['fecha' => $data['fecha'], 'canal' => $data['canal']],
            [
                'inversion' => $inversion,
                'visitas' => $visitas,
                'leads' => $leads,
                'ventas' => $ventas,
                'ingresos' => $ingresos,
                'cac' => $leads > 0 ? round($inversion / $leads, 2) : null,
                'roas' => $inversion > 0 ? round($ingresos / $inversion, 4) : null,
                'conversion_rate' => round($ventas / $visitas, 4),
                'recompras' => (int) ($data['recompras'] ?? 0),
                'ticket_promedio' => round($ingresos / $ventas, 2),
                'abandono_carrito' => $data['abandono_carrito'] ?? null,
            ]
        );

        return back()->with('success', 'KPI de marketing guardado.');
    }

    public function inventario(): View
    {
        $movimientos = Schema::hasTable('inventario_movimientos')
            ? InventarioMovimiento::query()->orderByDesc('fecha')->limit(150)->get()
            : collect();

        // Resumen gráfico 30 días (entradas vs salidas)
        $invLabels = [];
        $invEntradas = [];
        $invSalidas = [];
        if (Schema::hasTable('inventario_movimientos')) {
            $desde = now()->subDays(29)->startOfDay();
            $hasta = now()->endOfDay();
            $dayExpr = DbDateAgg::exprDay('fecha');
            $rows = DB::table('inventario_movimientos')
                ->whereBetween('fecha', [$desde, $hasta])
                ->selectRaw("{$dayExpr} as k, tipo, SUM(cantidad) as c")
                ->groupByRaw("{$dayExpr}, tipo")
                ->get();

            $map = [];
            foreach ($rows as $r) {
                $map[$r->k][$r->tipo] = (int) $r->c;
            }
            for ($d = 0; $d < 30; $d++) {
                $day = $desde->copy()->addDays($d)->toDateString();
                $invLabels[] = $day;
                $invEntradas[] = (int) ($map[$day]['entrada'] ?? 0);
                $invSalidas[] = (int) ($map[$day]['salida'] ?? 0);
            }
        }

        return view('erp.inventario', compact('movimientos', 'invLabels', 'invEntradas', 'invSalidas'));
    }

    public function stock(Request $request): View
    {
        $buscar = trim((string) $request->query('q', ''));
        $orden = (string) $request->query('orden', 'stock_asc'); // stock_asc|stock_desc|nombre
        $topN = (int) $request->query('top', 15);
        if ($topN < 5) $topN = 5;
        if ($topN > 50) $topN = 50;

        if (!Schema::hasTable('productos')) {
            return view('erp.stock', [
                'productos' => collect(),
                'q' => $buscar,
                'orden' => $orden,
            ]);
        }

        $query = Producto::query()->with('categoria');
        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('codigo', 'like', "%{$buscar}%");
            });
        }

        if ($orden === 'stock_desc') {
            $query->orderByDesc('stock');
        } elseif ($orden === 'nombre') {
            $query->orderBy('nombre');
        } else {
            $query->orderBy('stock');
        }

        $productos = $query->paginate(50)->withQueryString();

        // Gráfico: distribución de stock (conteo de productos por rangos)
        $stockBinsLabels = ['0', '1-5', '6-10', '11-20', '21-50', '51-100', '101+'];
        $stockBinsSeries = [0, 0, 0, 0, 0, 0, 0];
        if (Schema::hasTable('productos')) {
            $rows = DB::table('productos')->select('stock')->get();
            foreach ($rows as $r) {
                $s = (int) $r->stock;
                if ($s === 0) $stockBinsSeries[0]++; 
                elseif ($s <= 5) $stockBinsSeries[1]++;
                elseif ($s <= 10) $stockBinsSeries[2]++;
                elseif ($s <= 20) $stockBinsSeries[3]++;
                elseif ($s <= 50) $stockBinsSeries[4]++;
                elseif ($s <= 100) $stockBinsSeries[5]++;
                else $stockBinsSeries[6]++;
            }
        }

        // Gráfico: Top productos por stock (barras)
        $topProdLabels = [];
        $topProdSeries = [];
        if (Schema::hasTable('productos')) {
            $rows = DB::table('productos')
                ->selectRaw('nombre, stock')
                ->orderByDesc('stock')
                ->limit($topN)
                ->get();
            $topProdLabels = $rows->pluck('nombre')->map(function ($n) {
                $n = (string) $n;
                return mb_strlen($n) > 28 ? mb_substr($n, 0, 28).'…' : $n;
            })->all();
            $topProdSeries = $rows->pluck('stock')->map(fn ($x) => (int) $x)->all();
        }

        return view('erp.stock', [
            'productos' => $productos,
            'q' => $buscar,
            'orden' => $orden,
            'stockBinsLabels' => $stockBinsLabels,
            'stockBinsSeries' => $stockBinsSeries,
            'topN' => $topN,
            'topProdLabels' => $topProdLabels,
            'topProdSeries' => $topProdSeries,
        ]);
    }

    public function stockBajo(Request $request): View
    {
        $umbral = (int) $request->query('umbral', 20);
        if ($umbral < 0) {
            $umbral = 0;
        }
        if ($umbral > 1000000) {
            $umbral = 1000000;
        }

        $productos = Schema::hasTable('productos')
            ? Producto::query()
                ->with('categoria')
                ->where('stock', '<', $umbral)
                ->orderBy('stock')
                ->paginate(50)
                ->withQueryString()
            : collect();

        // Gráfico: stock bajo por categoría
        $catLabels = [];
        $catSeries = [];
        if (Schema::hasTable('productos') && Schema::hasTable('categorias')) {
            $rows = DB::table('productos as pr')
                ->leftJoin('categorias as c', 'c.id', '=', 'pr.categoria_id')
                ->where('pr.stock', '<', $umbral)
                ->selectRaw("COALESCE(c.nombre, 'Sin categoría') as n, COUNT(*) as c")
                ->groupByRaw("COALESCE(c.nombre, 'Sin categoría')")
                ->orderByDesc('c')
                ->limit(12)
                ->get();
            $catLabels = $rows->pluck('n')->all();
            $catSeries = $rows->pluck('c')->map(fn ($x) => (int) $x)->all();
        }

        return view('erp.stock-bajo', compact('productos', 'umbral', 'catLabels', 'catSeries'));
    }
}
