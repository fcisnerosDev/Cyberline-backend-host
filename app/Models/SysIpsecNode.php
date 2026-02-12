<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SysIpsecNode extends Model
{
    use HasFactory;

    protected $table = 'sys_ipsec_nodes';

    protected $primaryKey = 'id';

    public $timestamps = false; // porque ya manejas fechas manualmente

    protected $fillable = [
        'idNodo',
        'nombre',
        'urlWs',
        'ip',
        'puerto',
        'token',
        'timeout',
        'flgConexion',
        'fechaConexion',
        'mensajeMonitoreo',
        'fechaVerificacionMonitoreo',
        'flgMonitoreo',
        'flgMsjMonitoreo',
        'flgSyncHijo',
        'flgSyncPadre',
        'fechaSyncHijo',
        'fechaSyncPadre',
        'SyncParche',
        'idNodoPadre',
        'alert_telegram',
        'fechaUltimoLogCorreo',
        'flgEstado',
        'fechaRegistro',
        'idUsuario',
        'idUsuarioNodo'
    ];

    protected $casts = [
        'fechaConexion' => 'datetime',
        'fechaVerificacionMonitoreo' => 'datetime',
        'fechaSyncHijo' => 'datetime',
        'fechaSyncPadre' => 'datetime',
        'fechaUltimoLogCorreo' => 'datetime',
        'fechaRegistro' => 'datetime',
        'alert_telegram' => 'boolean',
        'timeout' => 'integer',
        'puerto' => 'integer',
        'SyncParche' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | SCOPES PROFESIONALES
    |--------------------------------------------------------------------------
    */

    public function scopeActivos($query)
    {
        return $query->where('flgEstado', '1');
    }

    public function scopeConectados($query)
    {
        return $query->where('flgConexion', '1');
    }

    public function scopeListosParaSync($query)
    {
        return $query->where('flgEstado', '1')
            ->where('flgConexion', '1')
            ->where('flgSyncHijo', '1');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function getBaseUrlAttribute()
    {
        $url = rtrim($this->urlWs, '/');

        if ($this->puerto) {
            return $url . ':' . $this->puerto;
        }

        return $url;
    }

    public function getIsOnlineAttribute()
    {
        return $this->flgConexion === '1';
    }
}
