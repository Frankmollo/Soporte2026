<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Support\Facades\Schema;

class PricingService
{
    public function esMayorista(int $usuarioId): bool
    {
        if (!Schema::hasTable('usuarios') || !Schema::hasColumn('usuarios', 'es_mayorista')) {
            return false;
        }

        $usuario = Usuario::query()->find($usuarioId);

        return (bool) ($usuario?->es_mayorista ?? false);
    }

    public function precioUnitario(Producto $producto, int $usuarioId): float
    {
        return $producto->precioParaCliente($this->esMayorista($usuarioId));
    }
}
