<?php

namespace App\Console\Commands;

use App\Models\LoteImportacion;
use App\Models\Producto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TumomitoRandomizarStock extends Command
{
    protected $signature = 'tumomito:randomizar-stock
                            {--min=1 : Stock mínimo aleatorio}
                            {--max=150 : Stock máximo aleatorio}
                            {--solo-cero=1 : 1=solo productos con stock=0, 0=todos}
                            {--dry-run : Solo mostrar cuántos se afectarían}';

    protected $description = 'Asigna stock aleatorio a productos (útil para demo/BI).';

    public function handle(): int
    {
        if (!Schema::hasTable('productos')) {
            $this->error('No existe la tabla `productos`.');
            return self::FAILURE;
        }

        $min = (int) $this->option('min');
        $max = (int) $this->option('max');
        if ($min < 0) $min = 0;
        if ($max < $min) $max = $min;

        $soloCero = ((int) $this->option('solo-cero')) === 1;

        $query = Producto::query();
        if ($soloCero) {
            $query->where('stock', '=', 0);
        }

        $total = $query->count();
        if ($this->option('dry-run')) {
            $this->info("Dry-run: se actualizarían {$total} productos (rango {$min}-{$max}).");
            return self::SUCCESS;
        }

        $usaLotes = Schema::hasTable('lotes_importacion');

        $actualizados = 0;
        $saltadosPorLotes = 0;

        $query->chunkById(300, function ($productos) use ($min, $max, $usaLotes, &$actualizados, &$saltadosPorLotes) {
            DB::transaction(function () use ($productos, $min, $max, $usaLotes, &$actualizados, &$saltadosPorLotes) {
                foreach ($productos as $p) {
                    $nuevo = random_int($min, $max);

                    if ($usaLotes) {
                        // Si el producto ya tiene lotes, no tocamos para no romper PEPS/UEPS.
                        $tiene = LoteImportacion::query()->where('producto_id', $p->id)->exists();
                        if ($tiene) {
                            $saltadosPorLotes++;
                            continue;
                        }

                        // Si no tiene lotes, creamos un lote de apertura con el stock aleatorio.
                        if ($nuevo > 0) {
                            LoteImportacion::create([
                                'producto_id' => $p->id,
                                'cantidad_inicial' => $nuevo,
                                'cantidad_disponible' => $nuevo,
                                'fecha_ingreso' => now(),
                                'costo_unitario' => max(0, (float) $p->precio),
                            ]);
                        }
                    }

                    $p->stock = $nuevo;
                    $p->saveQuietly();
                    $actualizados++;
                }
            });
        });

        $this->info("Stock aleatorio aplicado. Actualizados: {$actualizados}.");
        if ($usaLotes) {
            $this->line("Saltados (ya tenían lotes): {$saltadosPorLotes}.");
        }

        return self::SUCCESS;
    }
}

