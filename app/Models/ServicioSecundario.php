<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicioSecundario extends Model
{
    use HasFactory;
    protected $connection = 'mysql_hijo';
    protected $table = 'comServicio';
    protected $primaryKey = 'idServicio';
    public $timestamps = false;
    protected $fillable = [
        'idServicio',
        'idServicioNodo',
        'idNodoPerspectiva',
        'idSync',
        'IdNodoSync',
        'idEquipo',
        'idEquipoNodo',
        'idTipoServicio',
        'idTipoServicioNodo',
        'idIp',
        'idIpNodo',
        'puerto',
        'fechaInicio',
        'fechaTermino',
        'flgEstado',
        'fechaCreacion',
        'fechaRegistro',
        'fechaModificacion',
        'flgSync',
        'flgSyncHijo',
        'flgSyncPadre',
        'fechaSyncHijo',
        'fechaSyncPadre',
        'temporal'
    ];
}
