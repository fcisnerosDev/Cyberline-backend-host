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
}
