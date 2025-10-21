<?php

namespace App\Http\Controllers\Api;

use App\Helpers\MonitoreoHelper;
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


    // public function getListMonitoreoRevision(Request $request)
    // {
    //     $MonitoreoRevision = Monitoreo::with('equipo')
    //         ->where('flgEstado', "1")
    //         ->where('flgRevision', "1")
    //         ->where('flgStatus', "C")
    //         ->whereHas('nodoPerspectiva', function ($query) {
    //             $query->where('flgEstado', '1');
    //         })
    //         ->paginate(10);

    //     return response()->json($MonitoreoRevision);
    // }


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



    // Dashboard


    public function verificarNodo(Request $request)
    {
        if (!$request->isMethod('get')) {
            return response()->json([
                'estado' => false,
                'mensaje' => 'Consulta no válida',
                'datos' => null
            ]);
        }

        try {
            // Verificar conexión
            $conexion = SysNodo::verificarConexion();
            $conexion = $this->convertirTiempo($conexion);

            // Verificar monitoreo
            $monitoreo = SysNodo::getListaNodoEstadoMonitoreo('1', '0');

            if (!$monitoreo || empty($monitoreo['data'])) {
                $monitoreoProcesado = [];
                $mensajeMonitoreo = 'No se encontraron registros';
            } else {
                $monitoreoProcesado = $this->convertirTiempoMonitoreo($monitoreo['data']);
                $mensajeMonitoreo = $monitoreo['mensaje'] ?? 'Consulta de monitoreo exitosa';
            }

            return response()->json([
                'estado' => true,
                'mensaje' => 'Verificación de nodo completada',
                'datos' => [
                    'conexion' => [
                        'mensaje' => 'Conexión verificada',
                        'datos' => $conexion
                    ],
                    'monitoreo' => [
                        'mensaje' => $mensajeMonitoreo,
                        'datos' => $monitoreoProcesado
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'estado' => false,
                'mensaje' => 'Error durante la verificación: ' . $e->getMessage(),
                'datos' => null
            ]);
        }
    }

    public function getListMonitoreoCaidos(Request $request)
    {
        $idNodoPerspectiva = $request->query('idNodoPerspectiva'); // opcional

        $query = Monitoreo::with([
            'equipo' => function ($q) {
                $q->where('flgEstado', '1')->with([
                    'oficina' => function ($q2) {
                        $q2->where('flgEstado', '1');
                    }
                ]);
            },
            'Ip' => fn($q) => $q->where('flgEstado', '1'),
            'servicio' => fn($q) => $q->where('flgEstado', '1')->with([
                'maeMaestro' => fn($q2) => $q2->where('flgEstado', '1')
            ]),
            'frecuencia' => fn($q) => $q->where('flgEstado', '1'),
            'nodoPerspectiva' => fn($q) => $q->where('flgEstado', '1'),
        ])
            ->where('flgEstado', '1')
            ->where('flgRevision', '0')
            ->where('flgSolucionado', '0')
            ->where('idMonitoreoNodo', 'CYB')
            ->where(function ($q) {
                $q->whereIn('flgStatus', ['C']) // caídos
                    ->orWhere('flgCondicionSolucionado', '1'); // o solucionados
            })
            ->whereHas('nodoPerspectiva', fn($q) => $q->where('flgEstado', '1'))
            ->whereHas('equipo', fn($q) => $q->where('flgEstado', '1'))
            ->whereHas('equipo.oficina', fn($q) => $q->where('flgEstado', '1'))
            ->whereHas('Ip', fn($q) => $q->where('flgEstado', '1'))
            ->whereHas('servicio', fn($q) => $q->where('flgEstado', '1'));

        // Filtrar por idNodoPerspectiva si se pasa
        if (!empty($idNodoPerspectiva)) {
            $query->where('idNodoPerspectiva', $idNodoPerspectiva);
        }

        $monitoreos = $query->get();
        $resultado = [];

        foreach ($monitoreos as $monitoreo) {
            if (isset($monitoreo->flgOcultarMonitoreo) && $monitoreo->flgOcultarMonitoreo === '1') {
                continue;
            }

            $equipo = $monitoreo->equipo ?? null;
            $oficina = $equipo->oficina ?? null;
            if (!$oficina || !$equipo) continue;
            if ($equipo->flgEstado !== '1') continue;

            if (!empty($equipo->idEquipoPerspectiva) && $equipo->idEquipoPerspectiva !== $monitoreo->idNodoPerspectiva) {
                continue;
            }

            $idOficinaPerspectiva = $oficina->idOficinaPerspectiva ?? null;
            if (
                $idOficinaPerspectiva
                && $idOficinaPerspectiva !== $monitoreo->idNodoPerspectiva
                && $idOficinaPerspectiva !== $oficina->idOficinaNodo
            ) {
                continue;
            }

            $keyOficina = $oficina->idOficina . ($oficina->idOficinaNodo ?? '');
            $keyEquipo = $monitoreo->idEquipo;

            // Inicializamos la oficina si no existe
            if (!isset($resultado[$keyOficina])) {
                $resultado[$keyOficina] = [
                    'idOficina' => $oficina->idOficina,
                    'idOficinaNodo' => $oficina->idOficinaNodo ?? null,
                    'idOficinaPerspectiva' => $idOficinaPerspectiva,
                    'nombre' => $oficina->nombre ?? '',
                    'equipos' => [],
                    'totalCaidos' => 0,
                    'totalActivos' => 0,
                    'bgColor' => null, // <-- color de la oficina
                ];
            }

            // Inicializamos el equipo si no existe
            if (!isset($resultado[$keyOficina]['equipos'][$keyEquipo])) {
                $resultado[$keyOficina]['equipos'][$keyEquipo] = [
                    'idEquipo' => $equipo->idEquipo,
                    'descripcion' => $equipo->descripcion ?? '',
                    'monitoreos' => [],
                    'caidos' => 0,
                    'activos' => 0,
                ];
            }

            // Calculamos tiempo y color
            $tiempoTranscurrido = Carbon::parse($monitoreo->fechaUltimaVerificacion)->diff(Carbon::now());
            $tiempoFormateado = sprintf(
                '%02d:%02d:%02d',
                $tiempoTranscurrido->days * 24 + $tiempoTranscurrido->h,
                $tiempoTranscurrido->i,
                $tiempoTranscurrido->s
            );

            $color = MonitoreoHelper::colorTiempoTranscurrido($monitoreo->fechaUltimaVerificacion);
            if ($monitoreo->flgCondicionSolucionado === '1' && $monitoreo->flgStatus === 'O') {
                $color = '#2f8c39';
            }

            // Contamos por estado
            if ($monitoreo->flgStatus === 'C') {
                $resultado[$keyOficina]['totalCaidos']++;
                $resultado[$keyOficina]['equipos'][$keyEquipo]['caidos']++;
            } elseif ($monitoreo->flgStatus === 'O') {
                $resultado[$keyOficina]['totalActivos']++;
                $resultado[$keyOficina]['equipos'][$keyEquipo]['activos']++;
            }

            // Agregamos el monitoreo
            $resultado[$keyOficina]['equipos'][$keyEquipo]['monitoreos'][] = [
                'idMonitoreo' => $monitoreo->idMonitoreo,
                'idMonitoreoNodo' => $monitoreo->idMonitoreoNodo,
                'idNodoPerspectiva' => $monitoreo->idNodoPerspectiva,
                'dscMonitoreo' => $monitoreo->dscMonitoreo,
                'servicio' => $monitoreo->servicio->maeMaestro->nombre ?? $monitoreo->servicio->nombre ?? '',
                'equipo' => $monitoreo->equipo->descripcion ?? '',
                'flgStatus' => $monitoreo->flgStatus,
                'ip' => $monitoreo->Ip->ip ?? '',
                'frecuencia' => $monitoreo->frecuencia->dscFrecuencia ?? '-',
                'fechaUltimaVerificacion' => $monitoreo->fechaUltimaVerificacion,
                'tiempoTranscurrido' => $tiempoFormateado,
                'color' => $color
            ];
        }

        // Reindexar los arrays y definir color según cantidad
        foreach ($resultado as &$oficina) {
            $oficina['equipos'] = array_values($oficina['equipos']);

            if ($oficina['totalCaidos'] > $oficina['totalActivos']) {
                $oficina['bgColor'] = '#CB1C1A'; // más caídos → rojo
            } elseif ($oficina['totalActivos'] > $oficina['totalCaidos']) {
                $oficina['bgColor'] = '#2f8c39'; // más activos → verde
            } else {
                $oficina['bgColor'] = '#ff8800'; // iguales → naranja
            }
        }

        return response()->json([
            'estado' => true,
            'mensaje' => 'Monitoreos obtenidos correctamente',
            'data' => array_values($resultado),
        ]);
    }




    public function getListMonitoreoRevision(Request $request)
    {
        $idNodoPerspectiva = $request->query('idNodoPerspectiva'); // opcional

        $query = Monitoreo::with([
            'equipo' => function ($q) {
                $q->where('flgEstado', '1')->with([
                    'oficina' => function ($q2) {
                        $q2->where('flgEstado', '1');
                    }
                ]);
            },
            'Ip' => fn($q) => $q->where('flgEstado', '1'),
            'servicio' => fn($q) => $q->where('flgEstado', '1')->with([
                'maeMaestro' => fn($q2) => $q2->where('flgEstado', '1')
            ]),
            'frecuencia' => fn($q) => $q->where('flgEstado', '1'),
            'nodoPerspectiva' => fn($q) => $q->where('flgEstado', '1'),
        ])
            ->where('flgEstado', '1')
            ->where('flgRevision', '1')
            ->where('idMonitoreoNodo', 'CYB')
            ->whereIn('flgStatus', ['C', 'O'])
            ->whereHas('nodoPerspectiva', fn($q) => $q->where('flgEstado', '1'))
            ->whereHas('equipo', fn($q) => $q->where('flgEstado', '1'))
            ->whereHas('equipo.oficina', fn($q) => $q->where('flgEstado', '1'))
            ->whereHas('Ip', fn($q) => $q->where('flgEstado', '1'))
            ->whereHas('servicio', fn($q) => $q->where('flgEstado', '1'));

        // Filtrar por idNodoPerspectiva si se pasa
        if (!empty($idNodoPerspectiva)) {
            $query->where('idNodoPerspectiva', $idNodoPerspectiva);
        }

        $monitoreos = $query->get();
        $resultado = [];

        foreach ($monitoreos as $monitoreo) {
            $equipo = $monitoreo->equipo ?? null;
            $oficina = $equipo->oficina ?? null;
            if (!$oficina || !$equipo) continue;

            // Ignorar equipos inactivos
            if ($equipo->flgEstado !== '1') continue;

            // Validar idEquipoPerspectiva si existe
            if (!empty($equipo->idEquipoPerspectiva) && $equipo->idEquipoPerspectiva !== $monitoreo->idNodoPerspectiva) {
                continue;
            }

            $idOficinaPerspectiva = $oficina->idOficinaPerspectiva ?? null;

            //  Solo ignorar oficina si su perspectiva existe y NO coincide ni con el monitoreo ni con su propio nodo
            if (
                $idOficinaPerspectiva
                && $idOficinaPerspectiva !== $monitoreo->idNodoPerspectiva
                && $idOficinaPerspectiva !== $oficina->idOficinaNodo
            ) {
                continue;
            }

            $keyOficina = $oficina->idOficina . ($oficina->idOficinaNodo ?? '');
            $keyEquipo = $monitoreo->idEquipo;

            if (!isset($resultado[$keyOficina])) {
                $resultado[$keyOficina] = [
                    'idOficina' => $oficina->idOficina,
                    'idOficinaNodo' => $oficina->idOficinaNodo ?? null,
                    'idOficinaPerspectiva' => $idOficinaPerspectiva,
                    'nombre' => $oficina->nombre ?? '',
                    'equipos' => [],
                ];
            }

            if (!isset($resultado[$keyOficina]['equipos'][$keyEquipo])) {
                $resultado[$keyOficina]['equipos'][$keyEquipo] = [
                    'idEquipo' => $equipo->idEquipo,
                    'descripcion' => $equipo->descripcion ?? '',
                    'monitoreos' => [],
                ];
            }

            $tiempoTranscurrido = Carbon::parse($monitoreo->fechaUltimaVerificacion)->diff(Carbon::now());
            $tiempoFormateado = sprintf(
                '%02d:%02d:%02d',
                $tiempoTranscurrido->days * 24 + $tiempoTranscurrido->h,
                $tiempoTranscurrido->i,
                $tiempoTranscurrido->s
            );
            $color = $monitoreo->flgStatus === 'O'
                ? '#2F8C39'
                : MonitoreoHelper::colorTiempoTranscurrido($monitoreo->fechaUltimaVerificacion);

            $resultado[$keyOficina]['equipos'][$keyEquipo]['monitoreos'][] = [
                'idMonitoreo' => $monitoreo->idMonitoreo,
                'idMonitoreoNodo' => $monitoreo->idMonitoreoNodo,
                'idNodoPerspectiva' => $monitoreo->idNodoPerspectiva,
                'dscMonitoreo' => $monitoreo->dscMonitoreo,
                'equipo' => $monitoreo->equipo->descripcion ?? '',
                'idEquipo' => $monitoreo->idEquipo,
                'servicio' => $monitoreo->servicio->maeMaestro->nombre ?? $monitoreo->servicio->nombre ?? '',
                'flgStatus' => $monitoreo->flgStatus,
                'ip' => $monitoreo->Ip->ip ?? '',
                'frecuencia' => $monitoreo->frecuencia->dscFrecuencia ?? '-',
                'fechaUltimaVerificacion' => $monitoreo->fechaUltimaVerificacion,
                'tiempoTranscurrido' => $tiempoFormateado,
                'color' => $color
            ];
        }

        foreach ($resultado as &$oficina) {
            $oficina['equipos'] = array_values($oficina['equipos']);
        }

        return response()->json([
            'estado' => true,
            'mensaje' => 'Monitoreos caídos obtenidos correctamente',
            'data' => array_values($resultado),
        ]);
    }

    public function MoverRevision($idNodoPerspectiva, $idMonitoreo)
    {
        $updated = Monitoreo::where('idNodoPerspectiva', $idNodoPerspectiva)
            ->where('idMonitoreo', $idMonitoreo)
            ->update(['flgRevision' => '1']);

        return response()->json([
            'success' => $updated > 0,
            'idNodoPerspectiva' => $idNodoPerspectiva,
            'idMonitoreo' => $idMonitoreo
        ]);
    }

    public function QuitarRevision($idNodoPerspectiva, $idMonitoreo)
    {
        $updated = Monitoreo::where('idNodoPerspectiva', $idNodoPerspectiva)
            ->where('idMonitoreo', $idMonitoreo)
            ->update(['flgRevision' => '0']);

        return response()->json([
            'success' => $updated > 0,
            'idNodoPerspectiva' => $idNodoPerspectiva,
            'idMonitoreo' => $idMonitoreo
        ]);
    }


    public function MonitoreoSolucionado($idNodoPerspectiva, $idMonitoreo)
    {
        // Obtener el monitoreo primero
        $monitoreo = Monitoreo::where('idNodoPerspectiva', $idNodoPerspectiva)
            ->where('idMonitoreo', $idMonitoreo)
            ->first();

        if (!$monitoreo) {
            return response()->json([
                'success' => false,
                'message' => 'Monitoreo no encontrado',
                'idNodoPerspectiva' => $idNodoPerspectiva,
                'idMonitoreo' => $idMonitoreo
            ]);
        }

        // Solo permitir si flgStatus es "O"
        if ($monitoreo->flgStatus !== 'O') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cambiar a solucionado una alerta con un estado no permitido',
                'flgStatus' => $monitoreo->flgStatus
            ]);
        }

        // Actualizar flgOcultarMonitoreo
        $monitoreo->flgSolucionado = '1';
        $monitoreo->save();

        return response()->json([
            'success' => true,
            'message' => 'Monitoreo marcado como solucionado',
            'idNodoPerspectiva' => $idNodoPerspectiva,
            'idMonitoreo' => $idMonitoreo
        ]);
    }
}
