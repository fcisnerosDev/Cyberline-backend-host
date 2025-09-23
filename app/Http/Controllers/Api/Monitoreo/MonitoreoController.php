<?php

namespace App\Http\Controllers\Api\Monitoreo;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use Illuminate\Http\Request;
use App\Models\Monitoreo;

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
        $ipDsc = $request->query('ipDsc');

        // Consulta base: solo monitoreos activos (flgEstado = "1") y servicios distintos a 'ping' y activos
        $query = Monitoreo::with(['equipo.oficina', 'Ip', 'servicio.maeMaestro'])
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
                'idEquipo' => $monitoreo->idEquipo,
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
        $ipDsc = $request->query('ipDsc');

        // Consulta base: solo monitoreos activos (flgEstado = "1") y solo servicios 'ping'
        $query = Monitoreo::with(['equipo.oficina', 'Ip', 'servicio.maeMaestro'])
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
                'idEquipo' => $monitoreo->idEquipo,
                'idEquipoNodo' => $monitoreo->idEquipoNodo,
                'descripcion' => $monitoreo->equipo->descripcion ?? '',
                'ip' => $monitoreo->Ip->ip ?? '',
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
}
