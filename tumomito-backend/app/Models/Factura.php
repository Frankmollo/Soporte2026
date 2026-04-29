<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model {
    protected $table = 'facturas';
    public $timestamps = false;
    protected $fillable = ['pedido_id', 'fecha_emision', 'nit_ci', 'razon_social', 'monto_total'];
}
