<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Services\InventarioPorLotesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class TumomitoInicializarLotes extends Command
{
    protected $signature = 'tumomito:inicializar-lotes-desde-stock {--dry-run : Solo mostrar cuenta de productos afectados}';

    protected $description = 'Crea lotes de apertura a partir del stock vigente cuando aún no existen registros por producto (alineado a PEPS/UEPS propuestos).';

    public function handle(InventarioPorLotesService $inventario): int
    {
        if (! Schema::hasTable('lotes_importacion')) {
            $this->error('Ejecute primero: php artisan migrate (tablas de lotes).');

            return self::FAILURE;
        }

        $n = 0;
        Producto::query()->chunkById(200, function ($productos) use ($inventario, &$n) {
            foreach ($productos as $producto) {
                if ($producto->stock <= 0) {
                    continue;
                }

                $yaTiene = $producto->lotes()->exists();
                if ($yaTiene) {
                    continue;
                }

                if ($this->option('dry-run')) {
                    ++$n;

                    continue;
                }

                $inventario->asegurarLoteDesdeStockSiSinLotes($producto);
                ++$n;
            }
        });

        if ($this->option('dry-run')) {
            $this->info("Simulación: se crearían lotes nuevos para aproximadamente {$n} productos con stock.");

            return self::SUCCESS;
        }

        $this->info("Se crearon {$n} lotes de apertura (productos sin lotes previos y stock > 0).");

        return self::SUCCESS;
    }
}
