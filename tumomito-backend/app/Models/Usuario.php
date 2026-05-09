<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model {
    protected $table = 'usuarios';
    public $timestamps = false;
    protected $fillable = ['nombre', 'email', 'contrasena', 'direccion', 'es_mayorista', 'rol'];

    /** Postgres: sin cast Laravel puede mandar 0/1 entero y falla el INSERT en column boolean. */
    protected $casts = [
        'es_mayorista' => 'boolean',
    ];

    // We can hide password when returning JSON
    protected $hidden = ['contrasena'];
}
