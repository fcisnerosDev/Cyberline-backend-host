<?php

namespace App\Http\Controllers\Api\Monitoreo;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Models\MonResumen;
use App\Models\Oficina;
use App\Models\SysNodo;
use Illuminate\Http\Request;
use App\Models\Monitoreo;
use Illuminate\Support\Facades\Http;

class MonitoreoController extends Controller
{
    public function getListMonitoreoServicios(Request $request)
    {
        // Parámetros de filtro
        $idOficina = $request->query('idOficina');
        $idOficinaNodo = $request->query('idOficinaNodo');
        $idCompania = $request->query('idCompania');
        $idCompaniaNodo = $request->query('idCompaniaNodo');
        $flgStatus = $request->query('flgStatus'); // filtro opcional
        $equipoDsc = $request->query('equipoDsc');
        $ipDsc = $request->query('ip');
        $idEquipo = $request->query('idEquipo');

        // Consulta base: solo monitoreos activos (flgEstado = "1") y servicios distintos a 'ping' y activos
        $query = Monitoreo::with(['equipo.oficina', 'Ip', 'servicio.maeMaestro', 'frecuencia'])
            ->where('flgEstado', '1') // monitoreo activo
            ->whereHas('servicio', function ($q) {
                $q->where('flgEstado', '1'); // servicio activo
            })
            ->whereHas('servicio.maeMaestro', function ($q) {
                $q->where('valor', '<>', 'ping'); // solo servicios distintos a 'ping'
            })
            ->whereHas('equipo', function ($q) {
                $q->where('flgEstado', '1'); // equipo activo
            })
            ->whereHas('Ip', function ($q) {
                $q->where('flgEstado', '1'); // IP activa
            });

        // Filtro por status del monitoreo (string)
        if ($flgStatus) {
            $query->where('flgStatus', $flgStatus);
        }
        if ($idEquipo) {
            $query->where('idEquipo', $idEquipo);
        }


        // Filtros opcionales sobre oficina a través de la relación equipo -> oficina
        if ($idOficina || $idOficinaNodo || $idCompania || $idCompaniaNodo) {
            $query->whereHas('equipo.oficina', function ($q) use ($idOficina, $idOficinaNodo, $idCompania, $idCompaniaNodo) {
                if ($idOficina) $q->where('idOficina', $idOficina);
                if ($idOficinaNodo) $q->where('idOficinaNodo', $idOficinaNodo);
                if ($idCompania) $q->where('idCompania', $idCompania);
                if ($idCompaniaNodo) $q->where('idCompaniaNodo', $idCompaniaNodo);
            });
        }

        // Filtro por descripción del equipo
        if ($equipoDsc) {
            $query->whereHas('equipo', function ($q) use ($equipoDsc) {
                $q->where('descripcion', 'like', "%$equipoDsc%");
            });
        }

        // Filtro por IP
        if ($ipDsc) {
            $query->whereHas('Ip', function ($q) use ($ipDsc) {
                $q->where('ip', 'like', "%$ipDsc%");
            });
        }

        $monitoreos = $query->get();

        // Agrupar por oficina usando datos desde la relación
        $resultado = [];
        foreach ($monitoreos as $monitoreo) {
            $oficina = $monitoreo->equipo->oficina ?? null;
            if (!$oficina) continue;

            $key = $oficina->idOficina . $oficina->idOficinaNodo;
            if (!isset($resultado[$key])) {
                $resultado[$key] = [
                    'id' => $key,
                    'idOficina' => $oficina->idOficina,
                    'idOficinaNodo' => $oficina->idOficinaNodo,
                    'nombre' => $oficina->nombre ?? '',
                    'equipos' => [],
                ];
            }

            $tiempoTranscurrido = \Carbon\Carbon::parse($monitoreo->fechaUltimaVerificacion)
                ->diff(\Carbon\Carbon::now());

            $tiempoFormateado = sprintf(
                '%d:%02d:%02d',
                $tiempoTranscurrido->days * 24 + $tiempoTranscurrido->h, // total horas incluyendo días
                $tiempoTranscurrido->i, // minutos
                $tiempoTranscurrido->s  // segundos
            );

            $equipo = [
                'idMonitoreo' => $monitoreo->idMonitoreo,
                'idMonitoreoNodo' => $monitoreo->idMonitoreoNodo,
                'idNodoPerspectiva' => $monitoreo->idNodoPerspectiva,
                'ip' => $monitoreo->Ip->ip ?? '',
                'fechaModificacionStatus' => $monitoreo->fechaModificacionStatus,
                'idEquipo' => $monitoreo->idEquipo,
                'FlgEstado' => $monitoreo->flgEstado,
                'frecuencia' => $monitoreo->frecuencia->dscFrecuencia ?? '-',
                'idEquipoNodo' => $monitoreo->idEquipoNodo,
                'descripcion' => $monitoreo->equipo->descripcion ?? '',
                'monitoreodescripcion' => $monitoreo->dscMonitoreo ?? '',
                'etiqueta' => $monitoreo->etiqueta ?? '',
                'flgStatus' => $monitoreo->flgStatus,
                'minutos' => $monitoreo->minutos ?? 0,
                'servicio' => $monitoreo->servicio->maeMaestro->nombre ?? '',
                'tiempoTranscurrido' => $tiempoFormateado,
            ];

            $resultado[$key]['equipos'][] = $equipo;
        }

        return response()->json([
            'estado' => true,
            'mensaje' => 'Monitoreos de servicios obtenidos correctamente',
            'data' => array_values($resultado),
        ]);
    }

    public function getListMonitoreoConectividad(Request $request)
    {
        $idOficina = $request->query('idOficina');
        $idOficinaNodo = $request->query('idOficinaNodo');
        $idCompania = $request->query('idCompania');
        $idCompaniaNodo = $request->query('idCompaniaNodo');
        $flgStatus = $request->query('flgStatus');
        $equipoDsc = $request->query('equipoDsc');
        $ipDsc = $request->query('ip');
        $idEquipo = $request->query('idEquipo');

        // Consulta base: solo monitoreos activos (flgEstado = "1") y solo servicios 'ping'
        $query = Monitoreo::with(['equipo.oficina', 'Ip', 'servicio.maeMaestro', 'frecuencia'])
            ->where('flgEstado', '1')
            ->whereHas('servicio.maeMaestro', function ($q) {
                $q->where('valor', 'ping'); // solo ping
            })
            ->whereHas('equipo', function ($q) {
                $q->where('flgEstado', '1'); // equipo activo
            })
            ->whereHas('Ip', function ($q) {
                $q->where('flgEstado', '1'); // IP activa
            });

        if ($flgStatus) {
            $query->where('flgStatus', $flgStatus);
        }
        if ($idEquipo) {
            $query->where('idEquipo', $idEquipo);
        }

        if ($idOficina || $idOficinaNodo || $idCompania || $idCompaniaNodo) {
            $query->whereHas('equipo.oficina', function ($q) use ($idOficina, $idOficinaNodo, $idCompania, $idCompaniaNodo) {
                if ($idOficina) $q->where('idOficina', $idOficina);
                if ($idOficinaNodo) $q->where('idOficinaNodo', $idOficinaNodo);
                if ($idCompania) $q->where('idCompania', $idCompania);
                if ($idCompaniaNodo) $q->where('idCompaniaNodo', $idCompaniaNodo);
            });
        }

        if ($equipoDsc) {
            $query->whereHas('equipo', function ($q) use ($equipoDsc) {
                $q->where('descripcion', 'like', "%$equipoDsc%");
            });
        }

        if ($ipDsc) {
            $query->whereHas('Ip', function ($q) use ($ipDsc) {
                $q->where('ip', 'like', "%$ipDsc%");
            });
        }

        $monitoreos = $query->get();

        $resultado = [];
        foreach ($monitoreos as $monitoreo) {
            $oficina = $monitoreo->equipo->oficina ?? null;
            if (!$oficina) continue;

            $key = $oficina->idOficina . $oficina->idOficinaNodo;
            if (!isset($resultado[$key])) {
                $resultado[$key] = [
                    'id' => $key,
                    'idOficina' => $oficina->idOficina,
                    'idOficinaNodo' => $oficina->idOficinaNodo,
                    'nombre' => $oficina->nombre ?? '',
                    'equipos' => [],
                ];
            }

            $tiempoTranscurrido = \Carbon\Carbon::parse($monitoreo->fechaUltimaVerificacion)
                ->diff(\Carbon\Carbon::now());

            $tiempoFormateado = sprintf(
                '%d:%02d:%02d',
                $tiempoTranscurrido->days * 24 + $tiempoTranscurrido->h,
                $tiempoTranscurrido->i,
                $tiempoTranscurrido->s
            );

            $equipo = [
                'idMonitoreo' => $monitoreo->idMonitoreo,
                'idMonitoreoNodo' => $monitoreo->idMonitoreoNodo,
                'idNodoPerspectiva' => $monitoreo->idNodoPerspectiva,
                'FlgEstado' => $monitoreo->flgEstado,
                'frecuencia' => $monitoreo->frecuencia->dscFrecuencia ?? '-',
                'idEquipo' => $monitoreo->idEquipo,
                'idEquipoNodo' => $monitoreo->idEquipoNodo,
                'descripcion' => $monitoreo->equipo->descripcion ?? '',
                'ip' => $monitoreo->Ip->ip ?? '',
                'servicio' => $monitoreo->servicio->maeMaestro->nombre ?? '',
                'fechaModificacionStatus' => $monitoreo->fechaModificacionStatus,
                'monitoreodescripcion' => $monitoreo->dscMonitoreo ?? '',
                'etiqueta' => $monitoreo->etiqueta ?? '',
                'flgStatus' => $monitoreo->flgStatus,
                'minutos' => $monitoreo->minutos ?? 0,
                'tiempoTranscurrido' => $tiempoFormateado,
            ];

            $resultado[$key]['equipos'][] = $equipo;
        }

        return response()->json([
            'estado' => true,
            'mensaje' => 'Monitoreos de conectividad obtenidos correctamente',
            'data' => array_values($resultado),
        ]);
    }

    public function getEquipos(Request $request)
    {
        $idOficina = $request->query('idOficina');
        $idOficinaNodo = $request->query('idOficinaNodo');
        $idCompania = $request->query('idCompania');
        $idCompaniaNodo = $request->query('idCompaniaNodo');
        $descripcion = $request->query('descripcion');
        $ipDsc = $request->query('ipDsc');

        $query = Equipo::with(['oficina', 'Ips'])
            ->where('flgEstado', '1'); // Solo equipos activos

        if ($idOficina || $idOficinaNodo || $idCompania || $idCompaniaNodo) {
            $query->whereHas('oficina', function ($q) use ($idOficina, $idOficinaNodo, $idCompania, $idCompaniaNodo) {
                if ($idOficina) $q->where('idOficina', $idOficina);
                if ($idOficinaNodo) $q->where('idOficinaNodo', $idOficinaNodo);
                if ($idCompania) $q->where('idCompania', $idCompania);
                if ($idCompaniaNodo) $q->where('idCompaniaNodo', $idCompaniaNodo);
            });
        }

        if ($descripcion) {
            $query->where('descripcion', 'like', "%$descripcion%");
        }

        if ($ipDsc) {
            $query->whereHas('Ips', function ($q) use ($ipDsc) {
                $q->where('ip', 'like', "%$ipDsc%");
            });
        }

        $equipos = $query->get();

        $resultado = [];
        foreach ($equipos as $equipo) {
            $oficina = $equipo->oficina ?? null;
            if (!$oficina) continue;

            $key = $oficina->idOficina . $oficina->idOficinaNodo;
            if (!isset($resultado[$key])) {
                $resultado[$key] = [
                    'id' => $key,
                    'idOficina' => $oficina->idOficina,
                    'idOficinaNodo' => $oficina->idOficinaNodo,
                    'nombre' => $oficina->nombre ?? '',
                    'equipos' => [],
                ];
            }

            $resultado[$key]['equipos'][] = [
                'idEquipo' => $equipo->idEquipo,
                'idEquipoNodo' => $equipo->idEquipoNodo,
                'descripcion' => $equipo->descripcion,
                'etiqueta' => $equipo->etiqueta ?? '',
                'ips' => $equipo->Ips->pluck('ip'), // listado de IPs
            ];
        }

        return response()->json([
            'estado' => true,
            'mensaje' => 'Equipos obtenidos correctamente',
            'data' => array_values($resultado),
        ]);
    }


    public function MonitoreoLog(Request $request)
    {
        $request->validate([
            'idCompaniaNodo'    => 'required|string',
            'idMonitoreo'       => 'required|integer',
            'idMonitoreoNodo'   => 'required|string',
            'idNodoPerspectiva' => 'required|string',
        ]);

        $idCompaniaNodo    = $request->idCompaniaNodo;
        $idMonitoreo       = $request->idMonitoreo;
        $idMonitoreoNodo   = $request->idMonitoreoNodo;
        $idNodoPerspectiva = $request->idNodoPerspectiva;

        // Buscar la URL del nodo remoto en sysNodo
        $nodo = SysNodo::where('idNodo', $idNodoPerspectiva)->first();

        if (!$nodo) {
            return response()->json([
                'estado'  => false,
                'mensaje' => "Nodo {$idNodoPerspectiva} no encontrado en sysNodo",
                'datos'   => null,
            ], 404);
        }

        // Armar URL remota
        $remoteUrl = rtrim($nodo->urlWs, '/') . '/servicio/monitoreolog/getListMonitoreoLog';

        // Hacer petición HTTP
        $response = Http::acceptJson()->get($remoteUrl, [
            'idCompaniaNodo'    => $idCompaniaNodo,
            'idMonitoreo'       => $idMonitoreo,
            'idMonitoreoNodo'   => $idMonitoreoNodo,
            'idNodoPerspectiva' => $idNodoPerspectiva,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return response()->json([
            'estado'  => false,
            'mensaje' => 'Error al conectar con el nodo remoto',
            'datos'   => null,
        ], 500);
    }

    public function MonitoreoResumen(Request $request)
    {
        $request->validate([
            'idNodoPerspectiva' => 'required|string',
            'idMonitoreoNodo'   => 'required|string',
            'idMonitoreo'       => 'required|integer',
        ]);

        $idNodoPerspectiva = $request->idNodoPerspectiva;
        $idMonitoreoNodo   = $request->idMonitoreoNodo;
        $idMonitoreo       = $request->idMonitoreo;

        // Base query
        $query = MonResumen::where('idNodoPerspectiva', $idNodoPerspectiva)
            ->where('idMonitoreoNodo', $idMonitoreoNodo)
            ->where('idMonitoreo', $idMonitoreo);

        // Si no se envían fechas, usamos hoy
        $fechaInicio = $request->filled('fechaInicio') ? $request->input('fechaInicio') : now()->format('Y-m-d');
        $fechaFin    = $request->filled('fechaFin') ? $request->input('fechaFin') : now()->format('Y-m-d');

        // Filtro por rango de fechas
        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fechaCreacion', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
        }

        $ResumenMonitoreo = $query->orderBy('fechaVerificacion', 'desc')->get();

        return response()->json([
            'estado'  => true,
            'mensaje' => 'Resumen de monitoreo obtenido correctamente',
            'data'    => $ResumenMonitoreo
        ]);
    }


    public function GetEquiposCompania(Request $request)
{
    $idOficina = $request->query('idOficina');
    $idOficinaNodo = $request->query('idOficinaNodo');
    $idCompania = $request->query('idCompania');
    $idCompaniaNodo = $request->query('idCompaniaNodo');
    $descripcion = $request->query('descripcion');
    $ipDsc = $request->query('ip');
    $flgStatus = $request->query('flgStatus');
    $idEquipo = $request->query('idEquipo');

    $query = Equipo::with([
        'oficina',
        'Ips',
        'monitoreos' => function ($q) use ($flgStatus) {
            $q->where('flgEstado', '1')
              ->whereHas('Ip', function ($q2) {
                  $q2->where('flgEstado', '1');
              });

            if ($flgStatus !== null && $flgStatus !== '') {
                $q->where('flgStatus', $flgStatus);
            }
        },
        'monitoreos.servicio.maeMaestro',
        'monitoreos.frecuencia',
        'monitoreos.Ip'
    ])
    ->where('flgEstado', '1');

    if ($idOficina || $idOficinaNodo || $idCompania || $idCompaniaNodo) {
        $query->whereHas('oficina', function ($q) use ($idOficina, $idOficinaNodo, $idCompania, $idCompaniaNodo) {
            if ($idOficina) $q->where('idOficina', $idOficina);
            if ($idOficinaNodo) $q->where('idOficinaNodo', $idOficinaNodo);
            if ($idCompania) $q->where('idCompania', $idCompania);
            if ($idCompaniaNodo) $q->where('idCompaniaNodo', $idCompaniaNodo);
        });
    }

    if ($descripcion) {
        $query->where('descripcion', 'like', "%$descripcion%");
    }

    if ($ipDsc) {
        $query->whereHas('Ips', function ($q) use ($ipDsc) {
            $q->where('ip', 'like', "%$ipDsc%");
        });
    }

    if ($idEquipo) {
        $query->where('idEquipo', $idEquipo);
    }

    $equipos = $query->get();

    $resultado = [];

    foreach ($equipos as $equipo) {
        $oficina = $equipo->oficina;
        if (!$oficina) continue;

        $key = $oficina->idOficina . $oficina->idOficinaNodo;

        if (!isset($resultado[$key])) {
            $resultado[$key] = [
                'id' => $key,
                'idOficina' => $oficina->idOficina,
                'idOficinaNodo' => $oficina->idOficinaNodo,
                'nombre' => $oficina->nombre ?? '',
                'equipos' => [],
            ];
        }

        $monitoreos = [];
        foreach ($equipo->monitoreos as $mon) {
            if ($flgStatus !== null && $flgStatus !== '' && $mon->flgStatus !== $flgStatus) {
                continue;
            }

            $tiempoTranscurrido = \Carbon\Carbon::parse($mon->fechaUltimaVerificacion)
                ->diff(\Carbon\Carbon::now());

            $tiempoFormateado = sprintf(
                '%d:%02d:%02d',
                $tiempoTranscurrido->days * 24 + $tiempoTranscurrido->h,
                $tiempoTranscurrido->i,
                $tiempoTranscurrido->s
            );

            $monitoreos[] = [
                'idMonitoreo' => $mon->idMonitoreo,
                'idMonitoreoNodo' => $mon->idMonitoreoNodo,
                'idNodoPerspectiva' => $mon->idNodoPerspectiva,
                'ip' => $mon->Ip->ip ?? '',
                'servicio' => $mon->servicio->maeMaestro->nombre ?? '',
                'frecuencia' => $mon->frecuencia->dscFrecuencia ?? '-',
                'descripcionMonitoreo' => $mon->dscMonitoreo ?? '',
                'etiqueta' => $mon->etiqueta ?? '',
                'flgStatus' => $mon->flgStatus,
                'minutos' => $mon->minutos ?? 0,
                'fechaModificacionStatus' => $mon->fechaModificacionStatus,
                'tiempoTranscurrido' => $tiempoFormateado,
            ];
        }

        // Si hay monitoreos o si no hay filtro de flgStatus
        if (count($monitoreos) > 0 || ($flgStatus === null || $flgStatus === '')) {
            $resultado[$key]['equipos'][] = [
                'idEquipo' => $equipo->idEquipo,
                'idEquipoNodo' => $equipo->idEquipoNodo,
                'descripcion' => $equipo->descripcion,
                'etiqueta' => $equipo->etiqueta ?? '',
                'ips' => $equipo->Ips->pluck('ip'),
                'monitoreos' => $monitoreos,
            ];
        }
    }

    return response()->json([
        'estado' => true,
        'mensaje' => 'Equipos y monitoreos obtenidos correctamente',
        'data' => array_values($resultado),
    ]);
}






    public function GetOficinasCompania(Request $request)
    {
        $idOficina = $request->query('idOficina');
        $idOficinaNodo = $request->query('idOficinaNodo');
        $idCompania = $request->query('idCompania');
        $idCompaniaNodo = $request->query('idCompaniaNodo');

        $query = Oficina::where('flgEstado', '1');

        if ($idOficina) {
            $query->where('idOficina', $idOficina);
        }

        if ($idOficinaNodo) {
            $query->where('idOficinaNodo', $idOficinaNodo);
        }

        if ($idCompania) {
            $query->where('idCompania', $idCompania);
        }

        if ($idCompaniaNodo) {
            $query->where('idCompaniaNodo', $idCompaniaNodo);
        }


        $oficinas = $query->get();

        $resultado = $oficinas->map(function ($oficina) {
            return [

                'idOficina' => $oficina->idOficina,
                'idOficinaNodo' => $oficina->idOficinaNodo,
                'nombre' => $oficina->nombre ?? '',
            ];
        });

        return response()->json([
            'estado' => true,
            'mensaje' => 'Oficinas obtenidas correctamente',
            'data' => $resultado,
        ]);
    }
}
