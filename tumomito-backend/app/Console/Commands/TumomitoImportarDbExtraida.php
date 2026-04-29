<?php

namespace App\Console\Commands;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class TumomitoImportarDbExtraida extends Command
{
    protected $signature = 'tumomito:importar-db-extraida
                            {--path= : Carpeta con CSV (por defecto ../db_extraida)}
                            {--dry-run : Solo analiza y muestra conteos}
                            {--stock-default=100 : Stock por defecto si no existe columna}
                            {--limit=0 : Limita filas por archivo (0 = sin límite)}';

    protected $description = 'Importa todos los CSV de db_extraida a MySQL (categorias y productos).';

    public function handle(): int
    {
        if (!Schema::hasTable('productos') || !Schema::hasTable('categorias')) {
            $this->error('No existen las tablas `productos`/`categorias`. Importa la BD o ejecuta migraciones.');
            return self::FAILURE;
        }

        $path = (string) ($this->option('path') ?: base_path('..'.DIRECTORY_SEPARATOR.'db_extraida'));
        $path = rtrim($path, "\\/ \t\n\r\0\x0B");
        if (!is_dir($path)) {
            $this->error("No existe la carpeta: {$path}");
            return self::FAILURE;
        }

        $files = glob($path.DIRECTORY_SEPARATOR.'*.csv') ?: [];
        if (empty($files)) {
            $this->warn("No se encontraron CSV en: {$path}");
            return self::SUCCESS;
        }

        $stockDefault = (int) $this->option('stock-default');
        $limit = (int) $this->option('limit');

        $total = 0;
        $totalInsert = 0;
        $totalUpdate = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $categoriaNombre = $this->categoriaDesdeArchivo($file);
            $categoria = Categoria::query()->firstOrCreate(['nombre' => $categoriaNombre]);

            [$headerIndex, $headers] = $this->detectarEncabezado($file);
            if ($headers === null) {
                $this->warn("Sin encabezados detectables: {$file}");
                continue;
            }

            $map = $this->mapearColumnas($headers);
            if ($map['nombre'] === null) {
                $this->warn("No se detectó columna NOMBRE/DESCRIPCIÓN en {$file}");
                continue;
            }

            $this->line("Importando {$categoriaNombre} | header@{$headerIndex} | ".basename($file));

            $rows = $this->leerDatosCsv($file, $headerIndex, $headers, $limit);
            $total += count($rows);

            if ($this->option('dry-run')) {
                continue;
            }

            DB::transaction(function () use ($rows, $map, $categoria, $stockDefault, &$totalInsert, &$totalUpdate, &$skipped) {
                $batch = [];
                foreach ($rows as $row) {
                    $nombre = $this->str($row[$map['nombre']] ?? '');
                    $nombre = trim($nombre);
                    if ($nombre === '' || strtolower($nombre) === 'nan') {
                        $skipped++;
                        continue;
                    }

                    $codigo = $map['codigo'] !== null ? trim($this->str($row[$map['codigo']] ?? '')) : '';
                    $precio = $map['precio'] !== null ? $this->toFloat($row[$map['precio']] ?? 0) : 0.0;
                    $stock = $map['stock'] !== null ? (int) round($this->toFloat($row[$map['stock']] ?? $stockDefault)) : $stockDefault;
                    if ($stock < 0) $stock = 0;

                    $batch[] = [
                        'codigo' => $codigo !== '' ? $codigo : null,
                        'nombre' => $nombre,
                        'precio' => $precio,
                        'stock' => $stock,
                        'categoria_id' => $categoria->id,
                    ];
                }

                if (empty($batch)) {
                    return;
                }

                // Upsert por (codigo,nombre,categoria_id) cuando codigo exista; si codigo es null, cae a (nombre,categoria_id).
                // Para mantenerlo simple, hacemos dos lotes: con codigo y sin codigo.
                $conCodigo = array_values(array_filter($batch, fn ($r) => !empty($r['codigo'])));
                $sinCodigo = array_values(array_filter($batch, fn ($r) => empty($r['codigo'])));

                if (!empty($conCodigo)) {
                    $before = Producto::query()->where('categoria_id', $categoria->id)->count();
                    DB::table('productos')->upsert(
                        $conCodigo,
                        ['codigo', 'nombre', 'categoria_id'],
                        ['precio', 'stock']
                    );
                    $after = Producto::query()->where('categoria_id', $categoria->id)->count();
                    // Aproximación: inserts = delta de conteo, updates = resto del batch
                    $ins = max(0, $after - $before);
                    $totalInsert += $ins;
                    $totalUpdate += max(0, count($conCodigo) - $ins);
                }

                if (!empty($sinCodigo)) {
                    $before = Producto::query()->where('categoria_id', $categoria->id)->count();
                    DB::table('productos')->upsert(
                        $sinCodigo,
                        ['nombre', 'categoria_id'],
                        ['precio', 'stock']
                    );
                    $after = Producto::query()->where('categoria_id', $categoria->id)->count();
                    $ins = max(0, $after - $before);
                    $totalInsert += $ins;
                    $totalUpdate += max(0, count($sinCodigo) - $ins);
                }
            });
        }

        $this->newLine();
        $this->info('Resumen importación');
        $this->line("Archivos: ".count($files));
        $this->line("Filas leídas: {$total}");
        if ($this->option('dry-run')) {
            $this->line('Dry-run: no se insertó nada.');
            return self::SUCCESS;
        }
        $this->line("Insert aprox: {$totalInsert}");
        $this->line("Update aprox: {$totalUpdate}");
        $this->line("Omitidas: {$skipped}");

        return self::SUCCESS;
    }

    private function categoriaDesdeArchivo(string $file): string
    {
        $name = pathinfo($file, PATHINFO_FILENAME);
        $name = str_replace('_', ' ', $name);
        return trim($name);
    }

    /**
     * Busca fila de encabezado en las primeras líneas.
     *
     * @return array{int, array<int,string>|null}
     */
    private function detectarEncabezado(string $file): array
    {
        $fh = fopen($file, 'rb');
        if (!$fh) {
            throw new RuntimeException("No se pudo abrir {$file}");
        }

        $bestIdx = 0;
        $bestHeaders = null;

        for ($i = 0; $i < 10; $i++) {
            $row = fgetcsv($fh);
            if ($row === false) break;

            $upper = array_map(fn ($v) => mb_strtoupper($this->str($v)), $row);
            $hit = 0;
            foreach ($upper as $v) {
                if (str_contains($v, 'CODIGO') || str_contains($v, 'CÓDIGO')) $hit++;
                if (str_contains($v, 'PRECIO') || str_contains($v, 'COSTO')) $hit++;
                if (str_contains($v, 'NOMBRE') || str_contains($v, 'DESCRI') || str_contains($v, 'ARTICULO')) $hit++;
            }
            if ($hit >= 2) {
                $bestIdx = $i;
                $bestHeaders = $row;
                break;
            }
            if ($hit > 0 && $bestHeaders === null) {
                $bestIdx = $i;
                $bestHeaders = $row;
            }
        }

        fclose($fh);

        if ($bestHeaders === null) {
            return [0, null];
        }

        // Normalizar headers: quitar vacíos
        $headers = [];
        foreach ($bestHeaders as $idx => $h) {
            $h = trim($this->str($h));
            $headers[$idx] = $h !== '' ? $h : "col_{$idx}";
        }

        return [$bestIdx, $headers];
    }

    /**
     * @param array<int,string> $headers
     * @return array{nombre:int|null, precio:int|null, codigo:int|null, stock:int|null}
     */
    private function mapearColumnas(array $headers): array
    {
        $colNombre = null;
        $colPrecio = null;
        $colCodigo = null;
        $colStock = null;

        foreach ($headers as $i => $h) {
            $u = mb_strtoupper($h);
            if (
                $colNombre === null
                && (
                    str_contains($u, 'DESCRI')
                    || str_contains($u, 'ARTIC')
                    || str_contains($u, 'NOMBRE')
                    || str_contains($u, 'DETALLE')
                    || $u === 'PRODUCTO'
                    || $u === 'COLUMNA2'
                    || $u === 'LINEA'
                )
            ) {
                $colNombre = $i;
                continue;
            }
            if ($colPrecio === null && (str_contains($u, 'PRECIO') || str_contains($u, 'COSTO'))) {
                $colPrecio = $i;
                continue;
            }
            if ($colCodigo === null && (str_contains($u, 'CODIGO') || str_contains($u, 'CÓDIGO'))) {
                $colCodigo = $i;
                continue;
            }
            if (
                $colStock === null
                && (
                    str_contains($u, 'STOCK')
                    || str_contains($u, 'CANTIDAD')
                    || str_contains($u, 'CONTEO')
                    || str_contains($u, 'ALMACEN')
                    || str_contains($u, 'SHOWROOM')
                )
            ) {
                $colStock = $i;
                continue;
            }
        }

        return ['nombre' => $colNombre, 'precio' => $colPrecio, 'codigo' => $colCodigo, 'stock' => $colStock];
    }

    /**
     * @param array<int,string> $headers
     * @return array<int,array<int,string>>
     */
    private function leerDatosCsv(string $file, int $headerIndex, array $headers, int $limit): array
    {
        $fh = fopen($file, 'rb');
        if (!$fh) {
            throw new RuntimeException("No se pudo abrir {$file}");
        }

        // Saltar hasta headerIndex
        for ($i = 0; $i < $headerIndex; $i++) {
            if (fgetcsv($fh) === false) break;
        }

        // Leer la fila header real
        $hdr = fgetcsv($fh);
        if ($hdr === false) {
            fclose($fh);
            return [];
        }

        $rows = [];
        $n = 0;
        while (($row = fgetcsv($fh)) !== false) {
            if ($limit > 0 && $n >= $limit) break;
            $rows[] = $row;
            $n++;
        }
        fclose($fh);

        return $rows;
    }

    private function toFloat(mixed $val): float
    {
        $s = trim($this->str($val));
        if ($s === '' || strtolower($s) === 'nan') return 0.0;
        $s = str_replace([' ', "\u{00A0}"], '', $s);
        $s = str_replace(',', '', $s);
        $s = str_replace('Bs.', '', $s);
        $s = str_replace('bs.', '', $s);
        $s = str_replace('BS.', '', $s);
        return is_numeric($s) ? (float) $s : 0.0;
    }

    private function str(mixed $v): string
    {
        if ($v === null) return '';
        if (is_string($v)) return $this->toUtf8($v);
        return $this->toUtf8((string) $v);
    }

    private function toUtf8(string $s): string
    {
        // Si ya es UTF-8 válido, lo dejamos.
        if (mb_detect_encoding($s, 'UTF-8', true)) {
            return $s;
        }
        $conv = @mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
        return $conv !== false ? $conv : $s;
    }
}

