<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model {
    protected $table = 'usuarios';
    public $timestamps = false;
    protected $fillable = ['nombre', 'email', 'contrasena', 'direccion', 'es_mayorista', 'rol'];
    
    // We can hide password when returning JSON
    protected $hidden = ['contrasena'];
}
