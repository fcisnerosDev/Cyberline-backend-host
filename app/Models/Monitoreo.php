<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Monitoreo extends Model
{
    use HasFactory;
    protected $table = 'monMonitoreo';
    protected $primaryKey = 'idMonitoreo';
    public $timestamps = false;
    protected $fillable = [
        'idMonitoreo',
        'idMonitoreoNodo',
        'idNodoPerspectiva',
        'idSync',
        'idSyncNodo',
        'idServicio',
        'idServicioNodo',
        'idEquipo',
        'idEquipoNodo',
        'idTipoServicio',
        'idTipoServicioNodo',
        'idIp',
        'idIpNodo',
        'idFrecuencia',
        'idFrecuenciaNodo',
        'idUsuario',
        'idUsuarioNodo',
        'dscMonitoreo',
        'etiqueta',
        'numReintentos',
        'paramametroScript',
        'flgMonitoreoIp',
        'paramNumPort',
        'paramNumPackets',
        'paramTimeout',
        'paramWarningUmbral',
        'paramCriticalUmbral',
        'flgRevision',
        'anotacion',
        'cuentasNotificacion',
        'intervaloNotificacion',
        'fechaUltimaVerificacion',
        'fechaUltimoCambio',
        'fechaUltimaNotificacion',
        'fechaActivacion',
        'fechaDesactivacion',
        'flgStatus',
        'flgStatusControl',
        'flgCondicionSolucionado',
        'flgOcultarMonitoreo',
        'flgSonido',
        'flgSolucionado',
        'flgEstado',
        'flgActivacionAutomatica',
        'fechaActivacionAutomatica',
        'fechaModificacion',
        'fechaModificacionStatus',
        'fechaCreacion',
        'fechaRegistro',
        'flgSync',
        'flgSyncHijo',
        'flgSyncPadre',
        'fechaSyncHijo',
        'fechaSyncPadre',
        'temporal',
        'cantidad_alertas',
        'porcentaje_alertas'
    ];


    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'idEquipo', 'idEquipo');
    }
    public function nodoPerspectiva()
    {
        return $this->belongsTo(SysNodo::class, 'idNodoPerspectiva', 'idNodo'); // 'idNodo' debe existir en sysNodo
    }
    public function ip()
    {
        return $this->belongsTo(Ip::class, 'idIp', 'idIp');
    }

    public function oficina()
    {
        return $this->belongsTo(Oficina::class, 'idOficina', 'id');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'idServicio', 'idServicio');
    }
    public function frecuencia()
    {
        return $this->belongsTo(Frecuencia::class, 'idFrecuencia', 'idFrecuencia');
    }

    public function nodo()
    {
        return $this->belongsTo(SysNodo::class, 'idNodoPerspectiva', 'idNodo');
    }

    public function telegram()
    {
        return $this->hasOne(
            MonMonitoreoTelegram::class,
            'idMonitoreo',
            'idMonitoreo'
        )->whereColumn(
                'mon_monitoreo_telegram.idMonitoreoNodo',
                'monMonitoreo.idMonitoreoNodo'
            )->whereColumn(
                'mon_monitoreo_telegram.idNodoPerspectiva',
                'monMonitoreo.idNodoPerspectiva'
            );
    }

}
