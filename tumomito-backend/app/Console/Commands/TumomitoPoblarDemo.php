<?php

namespace App\Console\Commands;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class TumomitoPoblarDemo extends Command
{
    protected $signature = 'tumomito:poblar-demo
                            {--productos=36 : Cantidad de productos de muestra (si la tabla está vacía o con --append)}
                            {--pedidos=35 : Pedidos demo para BI (0 = no generar)}
                            {--append : Añadir productos DEMO aunque ya existan otros}
                            {--skip-ventas : No ejecutar tumomito:generar-ventas-demo}
                            {--skip-lotes : No ejecutar tumomito:inicializar-lotes-desde-stock}';

    protected $description = 'Pobla Postgres (ej. Supabase): usuarios demo, categorías, productos y opcionalmente pedidos/lotes. Ejecutá con .env apuntando a la BD destino.';

    public function handle(): int
    {
        if (! Schema::hasTable('usuarios') || ! Schema::hasTable('categorias') || ! Schema::hasTable('productos')) {
            $this->error('Faltan tablas base. Ejecutá: php artisan migrate --force');

            return self::FAILURE;
        }

        $this->seedUsuarios();
        $categorias = $this->seedCategorias();

        $nuevaCargaProductos = (! Producto::query()->exists()) || $this->option('append');
        if (! $nuevaCargaProductos) {
            $this->warn('Ya hay productos en la BD. Omite creación de muestra (usa --append para añadir más).');
        } else {
            $this->seedProductos($categorias, (int) $this->option('productos'));
        }

        if (! $this->option('skip-lotes') && Schema::hasTable('lotes_importacion')) {
            $this->info('Inicializando lotes desde stock…');
            Artisan::call('tumomito:inicializar-lotes-desde-stock');
            $this->output->write(Artisan::output());
        }

        $pedidos = max(0, (int) $this->option('pedidos'));
        if ($pedidos > 0 && ! $this->option('skip-ventas')) {
            $uid = (int) (Usuario::query()->where('email', 'cliente@gmail.com')->value('id')
                ?: Usuario::query()->orderBy('id')->value('id'));
            if ($uid < 1) {
                $this->warn('No hay usuario para asociar pedidos demo.');
            } elseif (Producto::query()->where('stock', '>', 0)->doesntExist()) {
                $this->warn('Sin stock > 0; no se generan pedidos demo.');
            } else {
                $this->info("Generando {$pedidos} pedidos demo (usuario_id={$uid})…");
                Artisan::call('tumomito:generar-ventas-demo', [
                    '--pedidos' => $pedidos,
                    '--usuario' => $uid,
                ]);
                $this->output->write(Artisan::output());
            }
        }

        $guestId = (int) Usuario::query()->where('email', 'guest@tumomito.local')->value('id');
        if ($guestId > 0) {
            $this->info("Invitado sistema: id={$guestId}. Si hace falta, en .env: TUMOMITO_GUEST_USER_ID={$guestId}");
        }

        $this->info('Listo. Credenciales README: frank@gmail.com / 12345 (admin), cliente@gmail.com / 12345 (cliente).');

        return self::SUCCESS;
    }

    /**
     * @return array<int, Categoria>
     */
    private function seedCategorias(): array
    {
        $nombres = ['Bebidas', 'Snacks', 'Lácteos', 'Limpieza', 'Abarrotes'];
        $out = [];
        foreach ($nombres as $nombre) {
            $out[] = Categoria::query()->firstOrCreate(['nombre' => $nombre]);
        }

        return $out;
    }

    private function seedUsuarios(): void
    {
        $crear = [
            ['guest@tumomito.local', 'Invitado sistema', '-', 'cliente', false],
            ['frank@gmail.com', 'Frank Admin', '12345', 'admin', false],
            ['cliente@gmail.com', 'Cliente demo', '12345', 'cliente', false],
        ];

        foreach ($crear as [$email, $nombre, $pass, $rol, $mayorista]) {
            $attrs = [
                'nombre' => $nombre,
                'contrasena' => $pass,
                'rol' => $rol,
            ];
            if (Schema::hasColumn('usuarios', 'es_mayorista')) {
                $attrs['es_mayorista'] = $mayorista;
            }
            Usuario::query()->updateOrCreate(['email' => $email], $attrs);
            $this->line("Usuario: {$email} ({$rol})");
        }
    }

    /**
     * @param  array<int, Categoria>  $categorias
     */
    private function seedProductos(array $categorias, int $cantidad): void
    {
        $cantidad = max(1, $cantidad);
        $marcas = ['Alpha', 'Beta', 'Gamma', 'Delta'];
        $suffix = date('YmdHis');

        for ($i = 1; $i <= $cantidad; $i++) {
            $cat = $categorias[($i - 1) % count($categorias)];
            $precio = round(5 + ($i % 47) + ($i % 3) * 2.5, 2);
            $stock = 15 + ($i % 80);
            $codigo = 'DEMO-'.$suffix.'-'.$i;

            Producto::query()->create([
                'codigo' => $codigo,
                'nombre' => 'Producto demo '.$marcas[$i % count($marcas)].' '.$i,
                'precio' => $precio,
                'precio_mayorista' => round($precio * 0.88, 2),
                'stock' => $stock,
                'stock_minimo' => 5,
                'stock_maximo' => 500,
                'categoria_id' => $cat->id,
                'metodo_valoracion' => ($i % 2 === 0) ? 'PEPS' : 'UEPS',
            ]);
        }

        $this->info("Insertados {$cantidad} productos de muestra.");
    }
}
