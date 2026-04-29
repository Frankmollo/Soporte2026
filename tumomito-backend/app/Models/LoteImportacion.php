<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoteImportacion extends Model
{
    protected $table = 'lotes_importacion';

    public $timestamps = false;

    protected $fillable = [
        'producto_id',
        'cantidad_inicial',
        'cantidad_disponible',
        'fecha_ingreso',
        'costo_unitario',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'datetime',
            'costo_unitario' => 'decimal:4',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
