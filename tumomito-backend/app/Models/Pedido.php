<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model {
    protected $table = 'pedidos';
    public $timestamps = false; // Using default datetime but let's manage it carefully or disable Eloquent timestamps
    protected $fillable = [
        'usuario_id',
        'fecha',
        'estado',
        'canal_venta',
        'estado_pago',
        'estado_logistico',
        'total',
    ];

    public function detalles() {
        return $this->hasMany(DetallePedido::class, 'pedido_id');
    }
}
