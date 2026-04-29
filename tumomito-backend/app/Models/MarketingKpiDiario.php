<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingKpiDiario extends Model
{
    protected $table = 'marketing_kpis_diarios';

    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'canal',
        'inversion',
        'visitas',
        'leads',
        'ventas',
        'ingresos',
        'cac',
        'roas',
        'conversion_rate',
        'recompras',
        'ticket_promedio',
        'abandono_carrito',
    ];
}
