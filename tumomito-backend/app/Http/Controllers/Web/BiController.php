<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BiController extends Controller
{
    public function ventas(Request $request): View
    {
        if (!Schema::hasTable('pedidos')) {
            return view('erp.bi.ventas', [
                'desde' => null,
                'hasta' => null,
                'agrupacion' => 'dia',
                'labels' => [],
                'series' => [],
                'kpis' => ['ventas' => 0, 'pedidos' => 0, 'ticket_promedio' => 0],
            ]);
        }

        $agrupacion = (string) $request->query('agrupacion', 'dia'); // dia|semana|mes
        if (!in_array($agrupacion, ['dia', 'semana', 'mes'], true)) {
            $agrupacion = 'dia';
        }

        $desde = $request->query('desde') ? Carbon::parse($request->query('desde'))->startOfDay() : now()->subDays(30)->startOfDay();
        $hasta = $request->query('hasta') ? Carbon::parse($request->query('hasta'))->endOfDay() : now()->endOfDay();

        $base = DB::table('pedidos')
            ->whereBetween('fecha', [$desde, $hasta]);

        $kVentas = (float) (clone $base)->sum('total');
        $kPedidos = (int) (clone $base)->count();
        $kTicket = $kPedidos > 0 ? round($kVentas / $kPedidos, 2) : 0;

        if ($agrupacion === 'dia') {
            $rows = (clone $base)
                ->selectRaw('DATE(fecha) as k, SUM(total) as v')
                ->groupByRaw('DATE(fecha)')
                ->orderByRaw('DATE(fecha)')
                ->get();
        } elseif ($agrupacion === 'semana') {
            $rows = (clone $base)
                ->selectRaw('YEAR(fecha) as y, WEEK(fecha, 1) as w, SUM(total) as v')
                ->groupByRaw('YEAR(fecha), WEEK(fecha, 1)')
                ->orderByRaw('YEAR(fecha), WEEK(fecha, 1)')
                ->get()
                ->map(function ($r) {
                    return (object) ['k' => sprintf('%d-W%02d', $r->y, $r->w), 'v' => $r->v];
                });
        } else {
            $rows = (clone $base)
                ->selectRaw("DATE_FORMAT(fecha, '%Y-%m') as k, SUM(total) as v")
                ->groupByRaw("DATE_FORMAT(fecha, '%Y-%m')")
                ->orderByRaw("DATE_FORMAT(fecha, '%Y-%m')")
                ->get();
        }

        $labels = $rows->pluck('k')->values()->all();
        $series = $rows->pluck('v')->map(fn ($x) => (float) $x)->values()->all();

        return view('erp.bi.ventas', [
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'agrupacion' => $agrupacion,
            'labels' => $labels,
            'series' => $series,
            'kpis' => ['ventas' => $kVentas, 'pedidos' => $kPedidos, 'ticket_promedio' => $kTicket],
        ]);
    }

    public function productos(Request $request): View
    {
        if (!Schema::hasTable('detalle_pedido') || !Schema::hasTable('productos') || !Schema::hasTable('pedidos')) {
            return view('erp.bi.productos', [
                'desde' => null,
                'hasta' => null,
                'top' => [],
                'bottom' => [],
                'hayVentas' => false,
            ]);
        }

        $desde = $request->query('desde') ? Carbon::parse($request->query('desde'))->startOfDay() : now()->subDays(30)->startOfDay();
        $hasta = $request->query('hasta') ? Carbon::parse($request->query('hasta'))->endOfDay() : now()->endOfDay();

        // Agregado de ventas por producto en el rango
        $agg = DB::table('detalle_pedido as dp')
            ->join('pedidos as p', 'p.id', '=', 'dp.pedido_id')
            ->whereBetween('p.fecha', [$desde, $hasta])
            ->groupBy('dp.producto_id')
            ->selectRaw('dp.producto_id as producto_id, SUM(dp.cantidad) as unidades, SUM(dp.subtotal) as ingresos');

        $hayVentas = DB::table('pedidos')->whereBetween('fecha', [$desde, $hasta])->exists();

        // Top: solo productos con ventas (si hay)
        $top = DB::query()
            ->fromSub($agg, 'v')
            ->join('productos as pr', 'pr.id', '=', 'v.producto_id')
            ->selectRaw('pr.id, pr.nombre, v.unidades as unidades, v.ingresos as ingresos')
            ->orderByDesc('v.unidades')
            ->limit(10)
            ->get();

        // Bottom: incluye productos con 0 ventas (LEFT JOIN)
        $bottom = DB::table('productos as pr')
            ->leftJoinSub($agg, 'v', 'v.producto_id', '=', 'pr.id')
            ->selectRaw('pr.id, pr.nombre, COALESCE(v.unidades, 0) as unidades, COALESCE(v.ingresos, 0) as ingresos')
            ->orderByRaw('COALESCE(v.unidades, 0) asc, pr.nombre asc')
            ->limit(10)
            ->get();

        return view('erp.bi.productos', [
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'top' => $top,
            'bottom' => $bottom,
            'hayVentas' => $hayVentas,
        ]);
    }
}

