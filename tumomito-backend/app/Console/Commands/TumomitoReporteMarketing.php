<?php

namespace App\Console\Commands;

use App\Models\MarketingKpiDiario;
use Illuminate\Console\Command;

class TumomitoReporteMarketing extends Command
{
    protected $signature = 'tumomito:marketing-resumen {--canal= : Filtra por canal}';

    protected $description = 'Muestra resumen de KPI de marketing (Facebook/TikTok/general).';

    public function handle(): int
    {
        $query = MarketingKpiDiario::query();
        if ($this->option('canal')) {
            $query->where('canal', $this->option('canal'));
        }

        $rows = $query->orderByDesc('fecha')->limit(30)->get();
        if ($rows->isEmpty()) {
            $this->warn('No hay datos de marketing registrados.');
            return self::SUCCESS;
        }

        $this->table(
            ['Fecha', 'Canal', 'Inversión', 'Ingresos', 'ROAS', 'Ventas', 'CAC'],
            $rows->map(fn ($r) => [
                $r->fecha,
                $r->canal,
                $r->inversion,
                $r->ingresos,
                $r->roas,
                $r->ventas,
                $r->cac,
            ])->all()
        );

        return self::SUCCESS;
    }
}
