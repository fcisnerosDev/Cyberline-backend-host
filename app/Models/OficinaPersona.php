<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OficinaPersona extends Model
{
    protected $table = 'comOficinaRelacionPersona';
    public $timestamps = false;
    protected $fillable = [
    'idOficinaPersonaNodo',
    'idNodoPerspectiva',
    'idPersona',
    'idPersonaNodo',
    'idOficina',
    'idOficinaNodo',
    'flgPrincipal',
    'fechaInicio',
    'fechaTermino',
    'flgEstado',
    'fechaRegistro',
    'fechaModificacion',
    'flgSyncHijo',
    'flgSyncPadre',
];
    use HasFactory;
}
