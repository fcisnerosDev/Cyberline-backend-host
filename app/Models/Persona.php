<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $table = 'maePersona';
    public $timestamps = false;
    protected $fillable = [
        'idPersonaNodo',
        'idPersonaPerspectiva',
        'idRol',
        'idRolNodo',
        'nombre',
        'apellidos',
        'usuario',
        'password',
        'flgEstado',
        'fechaRegistro',
        'fechaModificacion',
        'flgSyncHijo',
        'flgSyncPadre',
    ];
    use HasFactory;
}
