<?php

namespace App\Http\Controllers\Api;

use App\Models\cybTrabajo;
use Carbon\Carbon;
use App\Helpers\EstadoHelper;
use Illuminate\Support\Facades\Auth;
use App\Helpers\PersonaHelper;
use App\Helpers\companiaHelper;
use App\Helpers\AtencionHelper;
use App\Helpers\CorreoReportaHelper;
use App\Helpers\oficinaHelper;
use App\Helpers\TrabajoHelper;
use App\Http\Controllers\Controller;
use App\Models\Compania;
use App\Models\cybAtencion;
use App\Models\EstadoTicket;
use App\Models\Persona;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketsController extends Controller
{
    // public function indexPagination(Request $request)
    // {
    //     // Obtener el ID del usuario autenticado
    //     $idPersona = Auth::id();

    //     // Iniciar la consulta base con el filtro por usuario autenticado
    //     $query = Ticket::where(function ($q) use ($request, $idPersona) {
    //         if ($request->filled('idUsuarioResponsable')) {
    //             $q->where('idUsuarioResponsable', $request->input('idUsuarioResponsable'));
    //         } else {
    //             $q->where('idUsuarioResponsable', $idPersona);
    //         }
    //     })->orderBy('idTicket', 'desc');

    //     // Agregar filtro adicional si se envía un número en la consulta
    //     $numero = $request->query('numero');
    //     if (!empty($numero)) {
    //         $query->where('numero', $numero); // Buscar por valor exacto
    //     }
    //     $flgStatus = $request->query('flgStatus');
    //     if ($request->filled('idCompaniaSolicitante')) {
    //         $query->where('idCompaniaSolicitante', $request->input('idCompaniaSolicitante'));
    //     }


    //     if (!empty($flgStatus)) {
    //         // Convertir a array si se pasa como una cadena separada por comas
    //         $flgStatusArray = is_array($flgStatus) ? $flgStatus : explode(',', $flgStatus);

    //         $query->whereIn('flgStatus', $flgStatusArray);
    //     }




    //     // Obtener la paginación
    //     $response = $query->paginate(20);

    //     // Iterar sobre los tickets para enriquecer los datos
    //     foreach ($response as $ticket) {
    //         // Obtener el responsable
    //         $responsable = PersonaHelper::getResponsableById($ticket->idUsuarioResponsable);
    //         if ($responsable) {
    //             $ticket->responsable = $responsable->nombre . ' ' . $responsable->apellidos;
    //         } else {
    //             $ticket->responsable = 'No disponible';
    //         }

    //         // Obtener el solicitante
    //         $solicitante = PersonaHelper::getSolicitanteById($ticket->idUsuarioSolicitante, $ticket->idUsuarioSolicitanteNodo);

    //         if ($solicitante) {
    //             $ticket->solicitante = $solicitante->nombre . ' ' . $solicitante->apellidos;
    //         } else {
    //             $ticket->solicitante = 'No disponible';
    //         }

    //         // Obtener la compañía solicitante
    //         $idCompaniaSolicitante = $ticket->idCompaniaSolicitante; // O cualquier otra lógica para asignar el valor
    //         $CompaniaSolicitante = companiaHelper::getCompaniaById($ticket->idTicketNodo, $idCompaniaSolicitante);
    //         if ($CompaniaSolicitante) {
    //             $ticket->CompaniaSolicitante = $CompaniaSolicitante->nombreCorto;
    //         } else {
    //             $ticket->CompaniaSolicitante = 'No disponible';
    //         }

    //         // Obtener la oficina de la compañía solicitante
    //         $CompaniaSolicitanteOficina = oficinaHelper::getoficinaById($ticket->idOficina);
    //         $ticket->CompaniaSolicitanteOficina = $CompaniaSolicitanteOficina ? $CompaniaSolicitanteOficina->nombre : 'No disponible';
    //     }

    //     // Devolver la respuesta en formato JSON con los tickets modificados
    //     return response()->json($response);
    // }

    public function indexPagination(Request $request)
    {
        // Obtener usuario autenticado
        $user = auth()->user();
        $idPersona = $user->idPersona ?? $request->user()->idPersona ?? null;
        $idPersonaNodo = $user->idPersonaNodo ?? $request->user()->idPersonaNodo ?? null;

        // Consulta base
        $query = Ticket::query()->orderBy('idTicket', 'desc');

        // Si se envía un idUsuarioResponsable, usarlo como filtro
        if ($request->filled('idUsuarioResponsable')) {
            $query->where('idUsuarioResponsable', $request->input('idUsuarioResponsable'));
        }
        // Si no se envía, filtrar por el usuario autenticado
        elseif ($idPersona && $idPersonaNodo) {
            $query->where(function ($q) use ($idPersona, $idPersonaNodo) {
                $q->where('idUsuarioResponsable', $idPersona)
                    ->where('idUsuarioResponsableNodo', $idPersonaNodo);
            });
        }

        // Filtro por número exacto
        if ($numero = $request->query('numero')) {
            $query->where('numero', $numero);
        }

        // Filtro por compañía solicitante
        if ($request->filled('idCompaniaSolicitante')) {
            $query->where('idCompaniaSolicitante', $request->input('idCompaniaSolicitante'));
        }

        // Filtro por estado
        if ($flgStatus = $request->query('flgStatus')) {
            $flgStatusArray = is_array($flgStatus) ? $flgStatus : explode(',', $flgStatus);
            $query->whereIn('flgStatus', $flgStatusArray);
        }

        // Paginación
        $response = $query->paginate(20);

        // Enriquecer datos
        foreach ($response as $ticket) {
            $responsable = PersonaHelper::getResponsableById($ticket->idUsuarioResponsable);
            $ticket->responsable = $responsable ? $responsable->nombre . ' ' . $responsable->apellidos : 'No disponible';

            $solicitante = PersonaHelper::getSolicitanteById($ticket->idUsuarioSolicitante, $ticket->idUsuarioSolicitanteNodo);
            $ticket->solicitante = $solicitante ? $solicitante->nombre . ' ' . $solicitante->apellidos : 'No disponible';

            $compania = companiaHelper::getCompaniaById($ticket->idTicketNodo, $ticket->idCompaniaSolicitante);
            $ticket->CompaniaSolicitante = $compania ? $compania->nombreCorto : 'No disponible';

            $oficina = oficinaHelper::getoficinaById($ticket->idOficina);
            $ticket->CompaniaSolicitanteOficina = $oficina ? $oficina->nombre : 'No disponible';

            $ticket->estadoNombre = EstadoHelper::getNombreEstado($ticket->idEstado);
        }

        return response()->json($response);
    }





    public function DetailTicket($numero)
    {
        try {
            $ticket = Ticket::where('numero', $numero)->first();

            if (!$ticket) {
                return response()->json([
                    'status' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }

            // --- Helpers y datos descriptivos ---
            $responsable = PersonaHelper::getResponsableById($ticket->idUsuarioResponsable);
            $solicitante = PersonaHelper::getSolicitanteById($ticket->idUsuarioSolicitante, $ticket->idUsuarioSolicitanteNodo);
            $CompaniaSolicitante = companiaHelper::getCompaniaById($ticket->idTicketNodo, $ticket->idCompaniaSolicitante);
            $CompaniaSolicitanteOficina = oficinaHelper::getoficinaById($ticket->idOficina);
            $correoReporta = CorreoReportaHelper::getCorreosReportaById($ticket->idTicket);

            $ticket->responsable = $responsable ? "{$responsable->nombre} {$responsable->apellidos}" : 'No disponible';
            $ticket->solicitante = $solicitante ? "{$solicitante->nombre} {$solicitante->apellidos}" : 'No disponible';
            $ticket->CompaniaSolicitante = $CompaniaSolicitante ? $CompaniaSolicitante->nombreCorto : 'No disponible';
            $ticket->CompaniaSolicitanteOficina = $CompaniaSolicitanteOficina ? $CompaniaSolicitanteOficina->nombre : 'No disponible';
            $ticket->correoReporta = $correoReporta;

            // --- 1. Tiempo total del ticket (solicitud a cierre) ---
            if ($ticket->fechaSolicitud && $ticket->fechaCierre) {
                $inicio = Carbon::parse($ticket->fechaSolicitud);
                $fin = Carbon::parse($ticket->fechaCierre);
                $diff = $inicio->diff($fin);

                $ticket->tiempoTranscurrido = sprintf(
                    "%d días %d horas %d minutos",
                    $diff->days,
                    $diff->h,
                    $diff->i
                );
            } else {
                $ticket->tiempoTranscurrido = 'No disponible';
            }

            // --- 2. Tiempo total (creación a cierre, referencia) ---
            if ($ticket->fechaCreacion && $ticket->fechaCierre) {
                $inicio = Carbon::parse($ticket->fechaCreacion);
                $fin = Carbon::parse($ticket->fechaCierre);
                $diff = $inicio->diff($fin);

                $ticket->tiempoTotal = sprintf(
                    "%d días %d horas %d minutos",
                    $diff->days,
                    $diff->h,
                    $diff->i
                );
            } else {
                $ticket->tiempoTotal = 'No disponible';
            }

            // --- 3. Procesar atenciones con sus trabajos asociados ---
            $atenciones = cybAtencion::where('idTicket', $ticket->idTicket)
                ->where('flgEstado', '1')
                ->orderBy('fechaCreacion', 'asc')
                ->get();

            $totalAtenciones = $atenciones->count();
            $tiempoTotalBrutoSegundos = 0;
            $ultimaAtencionDuracion = '00:00:00';
            $ultimaAtencionFecha = null;

            foreach ($atenciones as $atencion) {
                // Buscar trabajo asociado
                $trabajo = cybTrabajo::where('idTrabajo', $atencion->idTrabajo ?? null)->first();

                if ($trabajo && $trabajo->fechaInicio && $trabajo->fechaTermino) {
                    $inicio = Carbon::parse($trabajo->fechaInicio);
                    $fin = Carbon::parse($trabajo->fechaTermino);
                    $duracionSegundos = $fin->diffInSeconds($inicio);
                    $tiempoTotalBrutoSegundos += $duracionSegundos;

                    $ultimaAtencionDuracion = gmdate('H:i:s', $duracionSegundos);
                    $ultimaAtencionFecha = $fin;
                }
            }

            // --- 4. Resumen de tiempos ---
            $tiempoTotalBruto = gmdate('H:i:s', $tiempoTotalBrutoSegundos);

            $ticket->totalAtenciones = $totalAtenciones;
            $ticket->tiempoTotalBruto = $tiempoTotalBruto;
            $ticket->tiempoTotalPausa = '00:00:00'; // pendiente de implementar
            $ticket->tiempoEfectivo = $tiempoTotalBruto; // sin pausas
            $ticket->ultimaAtencionDuracion = $ultimaAtencionDuracion;
            $ticket->ultimaAtencionFecha = $ultimaAtencionFecha
                ? $ultimaAtencionFecha->format('Y-m-d H:i:s')
                : null;

            return response()->json(['status' => true, 'data' => $ticket]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener los detalles del ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function DetailAtencionesTicket($idTicket)
    {
        try {
            // Buscar atenciones activas del ticket
            $query = cybAtencion::where('idTicket', $idTicket)
                ->where('flgEstado', '1')
                ->orderBy('fechaCreacion', 'desc');

            if (!$query->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Atención no encontrada'
                ], 404);
            }

            // Paginación
            $atenciones = $query->paginate(10);

            foreach ($atenciones as $atencion) {
                // Obtener responsable
                $responsable = PersonaHelper::getResponsableById($atencion->idResponsable);
                $atencion->responsable = $responsable
                    ? "{$responsable->nombre} {$responsable->apellidos}"
                    : 'No disponible';

                // Obtener correos reportados
                $atencion->correoReporta = CorreoReportaHelper::getCorreosReportaByAtencionId($atencion->idAtencion);

                // Buscar trabajo asociado (si existe)
                $trabajo = null;
                if (!empty($atencion->idTrabajo)) {
                    $trabajo = cybTrabajo::where('idTrabajo', $atencion->idTrabajo)->first();
                }

                // Registrar datos del trabajo si existen
                $atencion->fechaInicioTrabajo = $trabajo->fechaInicio ?? null;
                $atencion->fechaTerminoTrabajo = $trabajo->fechaTermino ?? null;
                $atencion->idResponsableTrabajo = $trabajo->idResponsable ?? null;

                // Determinar inicio y fin
                if ($trabajo && $trabajo->fechaInicio && $trabajo->fechaTermino) {
                    $inicio = Carbon::parse($trabajo->fechaInicio);
                    $fin = Carbon::parse($trabajo->fechaTermino);
                } elseif ($atencion->fechaCreacion && $atencion->fechaModificacion) {
                    // fallback si no hay datos en cybTrabajo
                    $inicio = Carbon::parse($atencion->fechaCreacion);
                    $fin = Carbon::parse($atencion->fechaModificacion);
                } else {
                    $inicio = null;
                    $fin = null;
                }

                // Calcular duración
                if ($inicio && $fin) {
                    $segundos = $fin->diffInSeconds($inicio);
                    $horas = floor($segundos / 3600);
                    $minutos = floor(($segundos % 3600) / 60);
                    $segundos = $segundos % 60;

                    $atencion->duracion = sprintf("%02d:%02d:%02d", $horas, $minutos, $segundos);
                } else {
                    $atencion->duracion = "00:00:00";
                }
            }

            return response()->json([
                'status' => true,
                'data' => $atenciones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener los detalles de las atenciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }






    public function getEstadosTicket(Request $request)
    {
        $response = EstadoTicket::orderBy('id', 'asc');
        $response = $response->get();
        return response()->json($response);
    }

    public function getCompaniaTicket(Request $request)
    {
        $response = Compania::where('flgEstado', operator: "1")
            ->orderBy('idCompania', 'asc')
            ->get();

        return response()->json($response);
    }

    public function getResponsablesTicket(Request $request)
    {
        $response = Persona::where('flgEstado', operator: "1")
            ->orderBy('idPersona', 'asc')
            ->get();

        return response()->json($response);
    }
}
