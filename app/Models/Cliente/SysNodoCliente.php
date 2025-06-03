<?php


namespace App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysNodoCliente extends Model
{
    protected $table = 'sysNodo';
    protected $primaryKey = 'idNodo';
    public $timestamps = false;

    protected $fillable = [
        'ip',
        'nombre',
        'flgMonitoreo',
        'mensajeMonitoreo',
        'flgMsjMonitoreo',
        'flgEstado',
        'idNodoPadre',
        'fechaConexion',
        'flgConexion',
        'flgSyncHijo',
        'flgSyncPadre',
        'fechaSyncHijo',
        'fechaSyncPadre',
    ];

    // Asegurar que idNodo e idNodoPadre sean tratados como strings
    protected $casts = [
        'idNodo' => 'string',
        'idNodoPadre' => 'string',
        'fechaConexion'=> 'string',
        'flgEstado'=> 'string',
         'flgConexion'=> 'string',
        'flgMonitoreo'=> 'string',
    ];
}
