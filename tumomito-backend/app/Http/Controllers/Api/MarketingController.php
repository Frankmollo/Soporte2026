<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketingKpiDiario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarketingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!Schema::hasTable('marketing_kpis_diarios')) {
            return response()->json([]);
        }
        $canal = $request->query('canal');
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $query = MarketingKpiDiario::query()->orderBy('fecha');
        if ($canal) {
            $query->where('canal', $canal);
        }
        if ($desde) {
            $query->whereDate('fecha', '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate('fecha', '<=', $hasta);
        }

        return response()->json($query->get());
    }

    public function upsert(Request $request): JsonResponse
    {
        if (!Schema::hasTable('marketing_kpis_diarios')) {
            return response()->json(['error' => 'Módulo de marketing no migrado'], 422);
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

        $calc = [
            'cac' => $leads > 0 ? round($inversion / $leads, 2) : null,
            'roas' => $inversion > 0 ? round($ingresos / $inversion, 4) : null,
            'conversion_rate' => round(((int) $data['ventas']) / $visitas, 4),
            'ticket_promedio' => round($ingresos / $ventas, 2),
            'recompras' => (int) ($data['recompras'] ?? 0),
            'abandono_carrito' => isset($data['abandono_carrito']) ? (float) $data['abandono_carrito'] : null,
        ];

        $kpi = MarketingKpiDiario::query()->updateOrCreate(
            ['fecha' => $data['fecha'], 'canal' => $data['canal']],
            array_merge($data, $calc)
        );

        return response()->json($kpi);
    }

    public function resumen(): JsonResponse
    {
        if (!Schema::hasTable('marketing_kpis_diarios')) {
            return response()->json([]);
        }

        $rows = MarketingKpiDiario::query()
            ->select([
                'canal',
                DB::raw('SUM(inversion) as inversion'),
                DB::raw('SUM(ingresos) as ingresos'),
                DB::raw('SUM(visitas) as visitas'),
                DB::raw('SUM(ventas) as ventas'),
            ])
            ->groupBy('canal')
            ->get();

        return response()->json($rows);
    }
}
