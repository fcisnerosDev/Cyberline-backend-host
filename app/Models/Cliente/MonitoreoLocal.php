<?php

namespace App\Models\Cliente;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoreoLocal extends Model
{
    protected $connection = 'mysql_hijo';

    use HasFactory;
    protected $table = 'monMonitoreo';
    protected $primaryKey = 'idMonitoreo';
    public $timestamps = false;
    protected $fillable = [
        'idMonitoreo', 'idMonitoreoNodo', 'idNodoPerspectiva', 'idSync', 'idSyncNodo',
        'idServicio', 'idServicioNodo', 'idEquipo', 'idEquipoNodo', 'idTipoServicio',
        'idTipoServicioNodo', 'idIp', 'idIpNodo', 'idFrecuencia', 'idFrecuenciaNodo',
        'idUsuario', 'idUsuarioNodo', 'dscMonitoreo', 'etiqueta', 'numReintentos',
        'paramametroScript', 'flgMonitoreoIp', 'paramNumPort', 'paramNumPackets',
        'paramTimeout', 'paramWarningUmbral', 'paramCriticalUmbral', 'flgRevision',
        'anotacion', 'cuentasNotificacion', 'intervaloNotificacion', 'fechaUltimaVerificacion',
        'fechaUltimoCambio', 'fechaUltimaNotificacion', 'fechaActivacion',
        'fechaDesactivacion', 'flgStatus', 'flgStatusControl', 'flgCondicionSolucionado',
        'flgOcultarMonitoreo', 'flgSonido', 'flgSolucionado', 'flgEstado',
        'flgActivacionAutomatica', 'fechaActivacionAutomatica', 'fechaModificacion',
        'fechaModificacionStatus', 'fechaCreacion', 'fechaRegistro', 'flgSync',
        'flgSyncHijo', 'flgSyncPadre', 'fechaSyncHijo', 'fechaSyncPadre', 'temporal',
        'cantidad_alertas', 'porcentaje_alertas'
    ];
}
