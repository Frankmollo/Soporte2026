<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class TumomitoCrearUsuario extends Command
{
    protected $signature = 'tumomito:crear-usuario
                            {email : Email del usuario}
                            {contrasena : Contraseña (prototipo)}
                            {--nombre= : Nombre visible}
                            {--direccion= : Dirección}
                            {--mayorista : Marca al usuario como mayorista (B2B)}
                            {--rol=cliente : Rol del usuario (cliente|admin)}';

    protected $description = 'Crea o actualiza un usuario en la tabla `usuarios` (login de la tienda/ERP).';

    public function handle(): int
    {
        if (!Schema::hasTable('usuarios')) {
            $this->error('No existe la tabla `usuarios`. Importa la base o ejecuta las migraciones necesarias.');
            return self::FAILURE;
        }

        $email = (string) $this->argument('email');
        $pass = (string) $this->argument('contrasena');
        $nombre = (string) ($this->option('nombre') ?: 'Usuario');
        $direccion = $this->option('direccion');
        $mayorista = (bool) $this->option('mayorista');
        $rol = (string) $this->option('rol');
        if (!in_array($rol, ['cliente', 'admin'], true)) {
            $rol = 'cliente';
        }

        $data = [
            'nombre' => $nombre,
            'contrasena' => $pass,
            'direccion' => $direccion,
        ];

        if (Schema::hasColumn('usuarios', 'es_mayorista')) {
            $data['es_mayorista'] = $mayorista;
        }
        if (Schema::hasColumn('usuarios', 'rol')) {
            $data['rol'] = $rol;
        }

        $u = Usuario::query()->updateOrCreate(['email' => $email], $data);

        $this->info("OK: usuario guardado. id={$u->id} email={$u->email} rol=".($u->rol ?? 'cliente')." mayorista=".(int)($u->es_mayorista ?? 0));
        return self::SUCCESS;
    }
}

