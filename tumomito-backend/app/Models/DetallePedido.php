<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedido extends Model
{
    protected $table = 'detalle_pedido';

    public $timestamps = false;

    protected $fillable = ['pedido_id', 'producto_id', 'cantidad', 'precio_unitario', 'subtotal'];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function distribucionLotes()
    {
        return $this->hasMany(DetallePedidoLote::class, 'detalle_pedido_id');
    }
}
