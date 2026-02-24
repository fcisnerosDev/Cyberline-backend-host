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

    public function monitoreos()
    {
        return $this->hasMany(Monitoreo::class, 'idNodoPerspectiva', 'idNodo');
    }

    public static function actualizarNodoRemoto()
    {
        try {

            //  Solo CSF
            $nodo = self::where('idNodo', 'CSF')->first();

            if (!$nodo) {
                return ['status' => false, 'msg' => 'Nodo CSF no encontrado'];
            }

            if (!$nodo->urlWs) {
                return ['status' => false, 'msg' => 'CSF sin URL configurada'];
            }

            $response = Http::timeout(10)->get($nodo->urlWs . '/nodo.php');

            if (!$response->ok()) {
                self::marcarSinConexionCSF($nodo, 'Error HTTP');
                return ['status' => false, 'msg' => 'Error HTTP'];
            }

            $json = $response->json();

            if (!isset($json['status']) || $json['status'] !== 'success') {
                self::marcarSinConexionCSF($nodo, 'Respuesta inválida');
                return ['status' => false, 'msg' => 'Respuesta inválida'];
            }

            if (empty($json['data'][0])) {
                self::marcarSinConexionCSF($nodo, 'Sin datos');
                return ['status' => false, 'msg' => 'Sin datos'];
            }

            $data = $json['data'][0];

            //  Normalizar ENUM
            $nodo->mensajeMonitoreo = $data['mensajeMonitoreo'] ?? '';

            $nodo->flgMsjMonitoreo = ($data['flgMsjMonitoreo'] == '1') ? '1' : '0';

            $nodo->flgConexion = in_array($data['flgConexion'], ['0', '1', '2'])
                ? $data['flgConexion']
                : '0';

            $nodo->fechaConexion = Carbon::now();
            $nodo->fechaVerificacionMonitoreo = Carbon::now();

            $nodo->save();

            return ['status' => true, 'msg' => 'CSF actualizado correctamente'];

        } catch (\Exception $e) {

            return [
                'status' => false,
                'msg' => $e->getMessage()
            ];
        }
    }
    private static function marcarSinConexionCSF($nodo, $mensaje)
    {
        $nodo->mensajeMonitoreo = $mensaje;
        $nodo->flgMsjMonitoreo = '1';
        $nodo->flgConexion = '0';
        $nodo->fechaConexion = Carbon::now();
        $nodo->save();
    }
}
