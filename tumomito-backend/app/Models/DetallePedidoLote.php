<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetallePedidoLote extends Model
{
    protected $table = 'detalle_pedido_lotes';

    public $timestamps = false;

    protected $fillable = [
        'detalle_pedido_id',
        'lote_id',
        'cantidad',
        'costo_unitario_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'costo_unitario_snapshot' => 'decimal:4',
        ];
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(LoteImportacion::class, 'lote_id');
    }

    public function detallePedido(): BelongsTo
    {
        return $this->belongsTo(DetallePedido::class, 'detalle_pedido_id');
    }
}
