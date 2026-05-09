<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\DbDateAgg;
use Carbon\CarbonInterface;
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
            $d = DbDateAgg::exprDay('fecha');
            $rows = (clone $base)
                ->selectRaw("{$d} as k, SUM(total) as v")
                ->groupByRaw($d)
                ->orderByRaw($d)
                ->get();
        } elseif ($agrupacion === 'semana') {
            $w = DbDateAgg::exprIsoWeek('fecha');
            $rows = (clone $base)
                ->selectRaw("{$w} as k, SUM(total) as v")
                ->groupByRaw($w)
                ->orderByRaw($w)
                ->get();
        } else {
            $m = DbDateAgg::exprMonth('fecha');
            $rows = (clone $base)
                ->selectRaw("{$m} as k, SUM(total) as v")
                ->groupByRaw($m)
                ->orderByRaw($m)
                ->get();
        }

        [$labels, $series] = $this->buildVentasTimeline($desde, $hasta, $agrupacion, $rows);

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

    /**
     * Llena día/semana/mes dentro del rango elegido para que el gráfico coincida con el filtro (huecos en 0).
     *
     * @param  \Illuminate\Support\Collection<int,\stdClass>|iterable  $rows  filas SQL con k, v
     * @return array{0: array<int, string>, 1: array<int, float>}
     */
    private function buildVentasTimeline(Carbon $desde, Carbon $hasta, string $agrupacion, iterable $rows): array
    {
        $map = [];
        foreach ($rows as $r) {
            $map[(string) $r->k] = (float) $r->v;
        }

        $labels = [];
        $series = [];

        if ($agrupacion === 'dia') {
            for (
                $d = $desde->copy()->startOfDay();
                $d->lte($hasta->copy()->startOfDay());
                $d->addDay()
            ) {
                $key = $d->toDateString();
                $labels[] = $key;
                $series[] = $map[$key] ?? 0.0;
            }

            return [$labels, $series];
        }

        if ($agrupacion === 'mes') {
            $cursor = $desde->copy()->startOfMonth()->startOfDay();
            $last = $hasta->copy()->startOfMonth()->startOfDay();
            while ($cursor->lte($last)) {
                $key = $cursor->format('Y-m');
                $labels[] = $key;
                $series[] = $map[$key] ?? 0.0;
                $cursor->addMonthNoOverflow();
            }

            return [$labels, $series];
        }

        $cur = $desde->copy()->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $end = $hasta->copy()->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        while ($cur->lte($end)) {
            $key = sprintf('%d-W%02d', $cur->isoWeekYear(), $cur->isoWeek());
            $labels[] = $key;
            $series[] = $map[$key] ?? 0.0;
            $cur->addWeek();
        }

        return [$labels, $series];
    }
}

