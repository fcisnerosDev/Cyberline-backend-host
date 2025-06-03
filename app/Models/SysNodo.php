<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysNodo extends Model
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
        'urlWs',
        'fechaSyncPadre',
    ];

    // Asegurar que idNodo e idNodoPadre sean tratados como strings
    protected $casts = [
        'idNodo' => 'string',
        'idNodoPadre' => 'string',
        'fechaConexion' => 'string',
        'flgEstado' => 'string',
        'flgConexion' => 'string',
        'flgMonitoreo' => 'string',
    ];


    public static function verificarConexion()
    {
        return DB::select('CALL sp_verificarConexion()');
    }

    public static function getListaNodoEstadoMonitoreo($flgMsjMonitoreo, $flgMonitoreo)
    {
        $result = DB::select("CALL sp_getListaNodoEstadoMonitoreo(?, ?)", [$flgMsjMonitoreo, $flgMonitoreo]);

        return [
            'data' => $result,
            'mensaje' => count($result) > 0 ? "Cuentas desactivadas" : "No se encuentran registros",
            'estado' => count($result) > 0 ? "1" : "0"
        ];
    }

    // correos monitoreo
    public static function getSysNodo()
    {
        $resultado = DB::select('CALL sp_getSysNodo()');
        return (array) $resultado[0];
    }
}
