<?php

namespace App\Http\Controllers\Api;

use App\Models\MonitoreoCorreo;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Monitoreo;
use App\Models\SysNodo;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getCompaniaNodo(Request $request)
    {
        $response = SysNodo::where('flgEstado', "1")
            ->orderBy('fechaSyncHijo', 'asc')
            ->get();


        return response()->json($response);
    }


    public function getDisponibilidadNodo()
    {
        $nodos = SysNodo::where('flgEstado', "1")
            ->where('flgMonitoreo', "2")
            ->where('flgConexion', "0")
            ->select('nombre', 'mensajeMonitoreo', 'fechaVerificacionMonitoreo')
            ->get();

        // Modificar el formato de fecha para mostrarlo en días, horas y minutos
        $response = $nodos->map(function ($nodo) {
            $fechaVerif = Carbon::parse($nodo->fechaVerificacionMonitoreo);
            $ahora = Carbon::now();

            $diferencia = $fechaVerif->diff($ahora);

            $tiempoTranscurrido = '';
            if ($diferencia->d > 0) {
                $tiempoTranscurrido .= $diferencia->d . ' días ';
            }
            if ($diferencia->h > 0) {
                $tiempoTranscurrido .= $diferencia->h . ' horas ';
            }
            if ($diferencia->i > 0) {
                $tiempoTranscurrido .= $diferencia->i . ' minutos';
            }

            $nodo->tiempoTranscurrido = trim($tiempoTranscurrido); // Agregar al resultado
            return $nodo;
        });

        return response()->json($response);
    }


    public function verificarConexion(Request $request)
    {
        if ($request->isMethod('get')) {
            try {
                $datos = SysNodo::verificarConexion();
                $datos = $this->convertirTiempo($datos); // Llamamos a la nueva función privada

                return response()->json([
                    'estado' => true,
                    'mensaje' => 'Conexión verificada',
                    'datos' => $datos
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'estado' => false,
                    'mensaje' => 'Error de conexión: ' . $e->getMessage(),
                    'datos' => null
                ]);
            }
        }

        return response()->json([
            'estado' => false,
            'mensaje' => 'Consulta no válida',
            'datos' => null
        ]);
    }


    public function getVerificacionMonitoreo(Request $request)
    {
        if ($request->isMethod('get')) {

            $datos = SysNodo::getListaNodoEstadoMonitoreo('1', '0');


            if (!$datos || empty($datos['data'])) {
                return response()->json([
                    'estado' => false,
                    'mensaje' => 'No se encontraron registros',
                    'datos' => []
                ]);
            }


            $datos['data'] = $this->convertirTiempoMonitoreo($datos['data']);

            return response()->json([
                'estado' => true,
                'mensaje' => $datos['mensaje'] ?? 'Consulta exitosa',
                'datos' => $datos['data']
            ]);
        }

        return response()->json([
            'estado' => false,
            'mensaje' => 'Consulta no válida',
            'datos' => null
        ]);
    }


    /**
     * Convierte el tiempo en un mensaje detallado de inactividad.
     */
    private function convertirTiempo($datos)
    {
        return collect($datos)->map(function ($nodo) {
            $dias = floor($nodo->tiempo / 1440);
            $horas = floor(($nodo->tiempo % 1440) / 60);
            $minutos = $nodo->tiempo % 60;

            $tiempoTranscurrido = [];
            if ($dias > 0) $tiempoTranscurrido[] = "$dias días";
            if ($horas > 0) $tiempoTranscurrido[] = "$horas horas";
            if ($minutos > 0) $tiempoTranscurrido[] = "$minutos minutos";

            $mensaje = "No se ha recibido notificación de conexión desde el nodo {$nodo->nombre} al nodo Cyberline desde hace " . implode(' y ', $tiempoTranscurrido) . ".";

            $nodo->mensajeTiempo = $mensaje; // Agregamos el mensaje detallado

            return $nodo;
        });
    }
    private function convertirTiempoMonitoreo($datos)
    {
        return collect($datos)->map(function ($nodo) {
            $dias = floor($nodo->tiempo / 1440);
            $horas = floor(($nodo->tiempo % 1440) / 60);
            $minutos = $nodo->tiempo % 60;

            $tiempoTranscurrido = [];
            if ($dias > 0) $tiempoTranscurrido[] = "$dias días";
            if ($horas > 0) $tiempoTranscurrido[] = "$horas horas";
            if ($minutos > 0) $tiempoTranscurrido[] = "$minutos minutos";

            $mensaje = " {$nodo->nombre} No se ha podido verificar que este monitoreando desde hace " . implode(' y ', $tiempoTranscurrido) . ".";

            $nodo->mensajeTiempo = $mensaje; // Agregamos el mensaje detallado

            return $nodo;
        });
    }
    public function getListMonitoreo(Request $request)
    {
        if ($request->isMethod('get')) {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'estado' => false,
                    'mensaje' => 'Usuario no autenticado',
                    'datos' => null
                ]);
            }

            // Asignamos los valores correctos
            $params = $request->query(); // Obtiene los parámetros GET

            $params['idCompania'] = $user->idPersonaNodo;
            $params['idCompaniaNodo'] = $user->idPersonaNodo;
            $params['idNodoPerspectiva'] = $user->idPersonaPerspectiva;

            return response()->json([
                'estado' => true,
                'mensaje' => 'Datos obtenidos correctamente',
                'datos' => $params
            ]);
        }

        return response()->json([
            'estado' => false,
            'mensaje' => 'Consulta no válida',
            'datos' => null
        ]);
    }


    public function getListMonitoreoRevision(Request $request)
    {
        $MonitoreoRevision = Monitoreo::with('equipo')
            ->where('flgEstado', "1")
            ->where('flgRevision', "1")
            ->where('flgStatus', "C")
            ->whereHas('nodoPerspectiva', function ($query) {
                $query->where('flgEstado', '1');
            })
            ->paginate(10);

        return response()->json($MonitoreoRevision);
    }


    public function getListMonitoreoRevisionCorreo(Request $request)
    {
        
        $MonitoreoCorreoRevision = MonitoreoCorreo::where('flgEstado', '1')
            ->where('flgRevision', '1')
            ->where('flgStatus', "C")
            ->get();

        $cantidad = $MonitoreoCorreoRevision->count();
        return response()->json([
            'cantidad' => $cantidad,
            'data' => $MonitoreoCorreoRevision
        ]);
    }
}
