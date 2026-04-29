<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Producto extends Model
{
    protected $table = 'productos';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'precio',
        'precio_mayorista',
        'stock',
        'stock_minimo',
        'stock_maximo',
        'categoria_id',
        'metodo_valoracion',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function lotes()
    {
        return $this->hasMany(LoteImportacion::class, 'producto_id');
    }

    /**
     * Evita SELECT * cuando falta metodo_valoracion (antes de migraciones PEPS/UEPS).
     */
    public function scopeCatalogCompatibility(Builder $query): Builder
    {
        $tabla = $query->getModel()->getTable();
        if (Schema::hasTable($tabla) && !Schema::hasColumn($tabla, 'metodo_valoracion')) {
            return $query->select(['id', 'codigo', 'nombre', 'precio', 'stock', 'categoria_id']);
        }

        return $query;
    }

    public function precioParaCliente(bool $esMayorista): float
    {
        if ($esMayorista && Schema::hasColumn($this->getTable(), 'precio_mayorista') && $this->precio_mayorista !== null) {
            return (float) $this->precio_mayorista;
        }

        return (float) $this->precio;
    }
}
