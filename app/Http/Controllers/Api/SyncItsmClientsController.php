<?php

namespace App\Http\Controllers\Api;

use App\Models\CybTicketItsmSync;
use App\Models\Oficina;
use App\Models\OficinaItsm;
use App\Models\OficinaPersona;
use App\Models\Persona;
use App\Models\CybAtencion;
use App\Services\ItsmFortunaSilverService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\CybAtencionItsmSync;
use App\Models\CybSolicitud;
use App\Models\CybSolicitudItsm;
use Illuminate\Support\Facades\DB;
class SyncItsmClientsController extends Controller
{
    protected $itsmFortunaSilverService;

    public function __construct(ItsmFortunaSilverService $itsmFortunaSilverService)
    {
        $this->itsmFortunaSilverService = $itsmFortunaSilverService;
    }
    // public function AllSyncITSM(Request $request, ItsmFortunaSilverService $itsm)
    // {
    //     $tickets = Ticket::where('idCompaniaBeneficiaro', '1007')
    //         ->where('idCompaniaBeneficiaroNodo', 'CYB')
    //         ->where('fechaCreacion', '>=', '2026-03-01 00:00:00')
    //         ->where('fechaCreacion', '<', '2026-04-10 00:00:00')
    //         ->whereIn('idSubCategoria', [1017, 1018, 1019, 1025, 1026, 1027])
    //         ->get();

    //     foreach ($tickets as $ticket) {

    //         $sync = CybTicketItsmSync::where('ticket_id', $ticket->idTicket)->first();

    //         $solicitante = Persona::where('idPersona', $ticket->idUsuarioSolicitante)->first();
    //         $responsable = Persona::where('idPersona', $ticket->idUsuarioResponsable)->first();

    //         $nombreSolicitante = $solicitante
    //             ? $solicitante->nombre . ' ' . $solicitante->apellidos
    //             : 'N/A';

    //         $nombreResponsable = $responsable
    //             ? $responsable->nombre . ' ' . $responsable->apellidos
    //             : 'N/A';

    //         $descripcion = $ticket->descripcion . "<br><br>" .
    //             "Solicitado por: " . $nombreSolicitante . "<br>" .
    //             "Atendido por: " . $nombreResponsable;

    //         try {

    //             // =========================
    //             // 1. SI YA EXISTE → ACTUALIZA
    //             // =========================
    //             if ($sync && $sync->ticket_id_itsm) {

    //                 //  STATUS SYNC
    //                 $itsm->syncStatusFromLocal(
    //                     $sync->ticket_id_itsm,
    //                     $ticket->flgStatus,
    //                     $ticket->fechaAtencion,
    //                     $ticket->fechaCierre
    //                 );
    //                 // dd([
    //                 //     'ticket_model_id' => $atenciones,
    //                 //     'atenciones' => CybAtencion::where('idTicket', $ticket->idTicket)
    //                 // ]);
    //                 //  FOLLOWUPS (CYB ATENCIONES)
    //                 $atenciones = CybAtencion::where('idTicket', $ticket->idTicket)
    //                     ->where('flgNoenviar', '0')
    //                     ->get();

    //                 // dd([
    //                 //     'ticket_model_id' => $atenciones,
    //                 //     // 'atenciones' => CybAtencion::where('idTicket', $ticket->idTicket)
    //                 // ]);

    //                 foreach ($atenciones as $atencion) {

    //                     //  VERIFICAR SI YA FUE ENVIADO A ITSM
    //                     $exists = CybAtencionItsmSync::where('id_atencion', $atencion->idAtencion)
    //                         ->first();

    //                     if ($exists) {
    //                         continue; // ya fue enviado → NO DUPLICA
    //                     }

    //                     //  ENVIAR A ITSM
    //                     $itsm->addFollowup(
    //                         $sync->ticket_id_itsm,
    //                         $atencion->reporte
    //                     );

    //                     //  GUARDAR CONTROL DE SINCRONIZACIÓN
    //                     CybAtencionItsmSync::create([
    //                         'id_atencion' => $atencion->idAtencion,
    //                         'id_ticket' => $ticket->idTicket,
    //                         'ticket_id_itsm' => $sync->ticket_id_itsm,
    //                         'estado' => 'ENVIADO',
    //                         'fecha_sync' => now(),
    //                         'fecha_actualizacion' => now(),
    //                     ]);
    //                 }



    //                 CybTicketItsmSync::updateOrCreate(
    //                     ['ticket_id' => $ticket->idTicket],
    //                     [
    //                         'ticket_id_itsm' => $sync->ticket_id_itsm,
    //                         'sync_itsm' => 1,
    //                         'estado' => 'ACTUALIZADO-SINCRONIZADO',
    //                         'fecha_actualizacion' => now(),
    //                     ]
    //                 );

    //                 continue;
    //             }

    //             // =========================
    //             // 2. SI NO EXISTE → CREA
    //             // =========================
    //             $response = $itsm->createTicketFortunaITSM([
    //                 'asunto' => $ticket->numero . ' - ' . ($ticket->asunto ?? ''),
    //                 'descripcion' => $descripcion,
    //                 'numero' => $ticket->numero,
    //                 'fechaCreacion' => $ticket->fechaCreacion,
    //             ]);

    //             $ticketIdItsm = $response['id'] ?? null;

    //             CybTicketItsmSync::updateOrCreate(
    //                 ['ticket_id' => $ticket->idTicket],
    //                 [
    //                     'ticket_id_itsm' => $ticketIdItsm,
    //                     'sync_itsm' => 1,
    //                     'estado' => 'CREADO-SINCRONIZADO',
    //                     'fecha_sync' => now(),
    //                     'fecha_actualizacion' => now(),
    //                 ]
    //             );

    //         } catch (\Exception $e) {

    //             CybTicketItsmSync::updateOrCreate(
    //                 ['ticket_id' => $ticket->idTicket],
    //                 [
    //                     'sync_itsm' => 0,
    //                     'estado' => $e->getMessage(),
    //                     'fecha_actualizacion' => now(),
    //                 ]
    //             );
    //         }
    //     }

    //     return response()->json([
    //         "status" => "ok",
    //         "count" => $tickets->count(),
    //         "message" => "sync done"
    //     ]);
    // }

    private function obtenerTicketItsmDesdeAsunto(?string $asunto): ?int
    {
        if (empty($asunto)) {
            return null;
        }

        preg_match('/Ticket Ref\. Fortuna Nro \[(\d+)\]/', $asunto, $matches);

        return isset($matches[1]) ? (int) $matches[1] : null;
    }

    public function AllSyncITSM(Request $request, ItsmFortunaSilverService $itsm)
    {
        $tickets = Ticket::where('idCompaniaBeneficiaro', '1007')
            ->where('idCompaniaBeneficiaroNodo', 'CYB')
            ->whereDate('fechaCreacion', today())
            ->whereIn('idSubCategoria', [1017, 1018, 1019, 1025, 1026, 1027])
            ->get();

        foreach ($tickets as $ticket) {

            try {
                /*
                |--------------------------------------------------------------------------
                | Obtener ID de Fortuna desde el asunto Cybernet
                |--------------------------------------------------------------------------
                | Ejemplo:
                | Ticket Ref. Fortuna Nro [959] - Ticket Prueba 2 - BOYA - EDR
                */
                $ticketIdItsm = $this->obtenerTicketItsmDesdeAsunto($ticket->asunto);

                if (!$ticketIdItsm) {
                    CybTicketItsmSync::updateOrCreate(
                        ['ticket_id' => $ticket->idTicket],
                        [
                            'ticket_id_itsm' => null,
                            'sync_itsm' => 0,
                            'estado' => 'NO SE ENCONTRÓ ID FORTUNA EN ASUNTO',
                            'fecha_actualizacion' => now(),
                        ]
                    );

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Actualizar estado en Fortuna / GLPI
                |--------------------------------------------------------------------------
                */
                $itsm->syncStatusFromLocal(
                    $ticketIdItsm,
                    $ticket->flgStatus,
                    $ticket->fechaAtencion,
                    $ticket->fechaCierre
                );

                /*
                |--------------------------------------------------------------------------
                | Enviar atenciones Cybernet como followups a Fortuna
                |--------------------------------------------------------------------------
                */
                $atenciones = cybAtencion::where('idTicket', $ticket->idTicket)
                    ->where('flgNoenviar', '0')
                    ->get();

                foreach ($atenciones as $atencion) {

                    $exists = CybAtencionItsmSync::where('id_atencion', $atencion->idAtencion)
                        ->where('ticket_id_itsm', $ticketIdItsm)
                        ->first();

                    if ($exists) {
                        continue;
                    }

                    $itsm->addFollowup(
                        $ticketIdItsm,
                        $atencion->reporte
                    );

                    CybAtencionItsmSync::create([
                        'id_atencion' => $atencion->idAtencion,
                        'id_ticket' => $ticket->idTicket,
                        'ticket_id_itsm' => $ticketIdItsm,
                        'estado' => 'ENVIADO',
                        'fecha_sync' => now(),
                        'fecha_actualizacion' => now(),
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Guardar control local de sincronización
                |--------------------------------------------------------------------------
                */
                CybTicketItsmSync::updateOrCreate(
                    ['ticket_id' => $ticket->idTicket],
                    [
                        'ticket_id_itsm' => $ticketIdItsm,
                        'sync_itsm' => 1,
                        'estado' => 'ACTUALIZADO-SINCRONIZADO-DESDE-ASUNTO',
                        'fecha_sync' => now(),
                        'fecha_actualizacion' => now(),
                    ]
                );

            } catch (\Exception $e) {

                CybTicketItsmSync::updateOrCreate(
                    ['ticket_id' => $ticket->idTicket],
                    [
                        'ticket_id_itsm' => $ticketIdItsm ?? null,
                        'sync_itsm' => 0,
                        'estado' => $e->getMessage(),
                        'fecha_actualizacion' => now(),
                    ]
                );
            }
        }

        return response()->json([
            "status" => "ok",
            "count" => $tickets->count(),
            "message" => "sync done sin crear tickets en ITSM"
        ]);
    }
    // public function ticketsPorCategoria()
    // {
    //     $response = $this->itsmFortunaSilverService
    //         ->ticketsPorCategoria(1);

    //     $tickets = collect($response['data']['data'] ?? [])
    //         ->map(function ($ticket) {

    //             $solicitanteId = $ticket['4'] ?? null;
    //             $tecnicoId = $ticket['5'] ?? null;
    //             $estadoId = $ticket['12'] ?? null;

    //             // Obtener solicitante
    //             $solicitante = $solicitanteId
    //                 ? $this->itsmFortunaSilverService->obtenerUsuario($solicitanteId)
    //                 : null;

    //             // Obtener técnico
    //             $tecnico = $tecnicoId
    //                 ? $this->itsmFortunaSilverService->obtenerUsuario($tecnicoId)
    //                 : null;

    //             $ticketDetalle = $this->itsmFortunaSilverService
    //                 ->obtenerDetalleTicket((int) ($ticket['2'] ?? 0));

    //             // Nombre estado GLPI
    //             $estadoNombre = match ((int) $estadoId) {
    //                 1 => 'Nuevo',
    //                 2 => 'En proceso (Asignado)',
    //                 3 => 'En proceso (Planificado)',
    //                 4 => 'Pendiente',
    //                 5 => 'Resuelto',
    //                 6 => 'Cerrado',
    //                 default => 'Desconocido',
    //             };

    //             $prioridadNombre = match ((int) ($ticketDetalle['priority'] ?? 0)) {
    //                 1 => 'Muy Baja',
    //                 2 => 'Baja',
    //                 3 => 'Media',
    //                 4 => 'Alta',
    //                 5 => 'Muy Alta',
    //                 6 => 'Crítica',
    //                 default => 'Desconocida',
    //             };

    //             $urgenciaNombre = match ((int) ($ticketDetalle['urgency'] ?? 0)) {
    //                 1 => 'Muy Baja',
    //                 2 => 'Baja',
    //                 3 => 'Media',
    //                 4 => 'Alta',
    //                 5 => 'Muy Alta',
    //                 6 => 'Crítica',
    //                 default => 'Desconocida',
    //             };

    //             $impactoNombre = match ((int) ($ticketDetalle['impact'] ?? 0)) {
    //                 1 => 'Muy Bajo',
    //                 2 => 'Bajo',
    //                 3 => 'Medio',
    //                 4 => 'Alto',
    //                 5 => 'Muy Alto',
    //                 6 => 'Crítico',
    //                 default => 'Desconocido',
    //             };

    //             return [
    //                 'ticket_id' => $ticket['2'] ?? null,
    //                 'titulo' => $ticket['1'] ?? null,
    //                 'titulo_cybernet' =>
    //                     'Ticket Ref. Fortuna Nro [' . ($ticket['2'] ?? null) . ']' .
    //                     ' - ' .
    //                     ($ticket['1'] ?? ''),
    //                 'detalle' => [
    //                     'descripcion' => html_entity_decode(
    //                         strip_tags($ticketDetalle['content'] ?? '')
    //                     ),

    //                     'prioridad' => [
    //                         'id' => $ticketDetalle['priority'] ?? null,
    //                         'nombre' => $prioridadNombre,
    //                     ],

    //                     'urgencia' => [
    //                         'id' => $ticketDetalle['urgency'] ?? null,
    //                         'nombre' => $urgenciaNombre,
    //                     ],

    //                     'impacto' => [
    //                         'id' => $ticketDetalle['impact'] ?? null,
    //                         'nombre' => $impactoNombre,
    //                     ],
    //                 ],

    //                 'estado' => [
    //                     'id' => $estadoId,
    //                     'nombre' => $estadoNombre,
    //                 ],

    //                 'ultima_modificacion' => $ticket['19'] ?? null,
    //                 'fecha_ticket' => $ticket['15'] ?? null,
    //                 'prioridad' => $ticket['3'] ?? null,

    //                 'categoria' => [
    //                     'id' => 1,
    //                     'nombre' => $ticket['7'] ?? null,
    //                 ],

    //                 'solicitante' => [
    //                     'id' => $solicitanteId,
    //                     'nombre' => trim(
    //                         ($solicitante['firstname'] ?? '') . ' ' .
    //                         ($solicitante['realname'] ?? '')
    //                     ),
    //                     'correo' => $solicitanteId
    //                         ? $this->itsmFortunaSilverService->obtenerCorreoUsuario((int) $solicitanteId)
    //                         : null,
    //                 ],

    //                 'tecnico' => [
    //                     'id' => $tecnicoId,
    //                     'nombre' => trim(
    //                         ($tecnico['firstname'] ?? '') . ' ' .
    //                         ($tecnico['realname'] ?? '')
    //                     ),
    //                     'correo' => $tecnicoId
    //                         ? $this->itsmFortunaSilverService->obtenerCorreoUsuario((int) $tecnicoId)
    //                         : null,
    //                 ],

    //                 'fecha_cierre' => $ticket['18'] ?? null,
    //             ];
    //         })
    //         ->values();

    //     return response()->json([
    //         'estado' => true,
    //         'tickets' => $tickets
    //     ]);
    // }


    public function ticketsPorCategoria()
    {
        $response = $this->itsmFortunaSilverService->ticketsActivos();

        if (!($response['estado'] ?? false)) {
            return response()->json($response, 500);
        }

        $tickets = collect($response['data'] ?? [])
            ->map(function ($ticket) {

                $ticketData = $this->armarTicketCybernet($ticket);

                if (CybSolicitudItsm::where('ticket_id_itsm', $ticketData['ticket_id'])->exists()) {
                    return [
                        'ticket_id' => $ticketData['ticket_id'],
                        'estado_sync' => 'ya_existe',
                        'asunto' => $ticketData['titulo_cybernet'],
                    ];
                }

                $resultadoSp = $this->crearSolicitudCybernet($ticketData);

                if ((int) $resultadoSp->codigo <= 0) {
                    throw new \Exception($resultadoSp->mensaje ?? 'No se pudo insertar solicitud');
                }

                CybSolicitudItsm::create([
                    'cyb_solicitud_id' => $resultadoSp->id,
                    'ticket_id_itsm' => $ticketData['ticket_id'],
                ]);

                return [
                    'ticket_id' => $ticketData['ticket_id'],
                    'cyb_solicitud_id' => $resultadoSp->id,
                    'estado_sync' => 'insertado-SYNC-FORTUNA',
                    'mensaje' => $resultadoSp->mensaje,
                    'asunto' => $ticketData['titulo_cybernet'],
                ];
            })
            ->values();

        return response()->json([
            'estado' => true,
            'tickets' => $tickets
        ]);
    }

    private function   crearSolicitudCybernet(array $ticketData)
    {
        $pdo = DB::connection()->getPdo();

        $stmt = $pdo->prepare("
        CALL sp_addSolicitud(
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            @o_msg, @o_codigo, @o_id
        )
    ");

        $stmt->execute([
            8,
            'CYB',
            0,
            'CYB',

            $ticketData['solicitante']['nombre'] ?? '',
            $ticketData['solicitante']['correo'] ?? '',
            $ticketData['titulo_cybernet'] ?? '',

            $ticketData['detalle']['descripcion'] ?? '',
            'fmcmail.com',
            $ticketData['solicitante']['correo'] ?? '',

            $ticketData['solicitante']['correo'] ?? '',
            $ticketData['tecnico']['correo'] ?? '',

            now()->format('Y-m-d H:i:s'),
        ]);

        $stmt->closeCursor();

        return DB::selectOne("
        SELECT 
            @o_msg AS mensaje,
            @o_codigo AS codigo,
            @o_id AS id
    ");
    }

    private function nombreEstadoGlpi($estadoId): string
    {
        return match ((int) $estadoId) {
            1 => 'Nuevo',
            2 => 'En proceso (Asignado)',
            3 => 'En proceso (Planificado)',
            4 => 'Pendiente',
            5 => 'Resuelto',
            6 => 'Cerrado',
            default => 'Desconocido',
        };
    }

    private function nombreNivelGlpi($nivelId): string
    {
        return match ((int) $nivelId) {
            1 => 'Muy Baja',
            2 => 'Baja',
            3 => 'Media',
            4 => 'Alta',
            5 => 'Muy Alta',
            6 => 'Crítica',
            default => 'Desconocida',
        };
    }
    // private function armarTicketCybernet(array $ticket): array
    // {
    //     $ticketId = (int) ($ticket['id'] ?? 0);

    //     $solicitanteId = $ticket['users_id_recipient'] ?? null;
    //     $tecnicoId = $ticket['users_id_lastupdater'] ?? null;
    //     $estadoId = $ticket['status'] ?? null;

    //     $solicitante = $solicitanteId
    //         ? $this->itsmFortunaSilverService->obtenerUsuario((int) $solicitanteId)
    //         : null;

    //     $tecnico = $tecnicoId
    //         ? $this->itsmFortunaSilverService->obtenerUsuario((int) $tecnicoId)
    //         : null;

    //     $solicitanteNombre = trim(
    //         ($solicitante['firstname'] ?? '') . ' ' .
    //         ($solicitante['realname'] ?? '')
    //     );

    //     $tecnicoNombre = trim(
    //         ($tecnico['firstname'] ?? '') . ' ' .
    //         ($tecnico['realname'] ?? '')
    //     );

    //     $solicitanteCorreo = $solicitanteId
    //         ? $this->itsmFortunaSilverService->obtenerCorreoUsuario((int) $solicitanteId)
    //         : null;

    //     $tecnicoCorreo = $tecnicoId
    //         ? $this->itsmFortunaSilverService->obtenerCorreoUsuario((int) $tecnicoId)
    //         : null;

    //     return [
    //         'ticket_id' => $ticketId,
    //         'titulo' => $ticket['name'] ?? null,
    //         'titulo_cybernet' =>
    //             'Ticket Ref. Fortuna Nro [' . $ticketId . '] - ' .
    //             ($ticket['name'] ?? ''),

    //         'detalle' => [
    //             'descripcion' => $this->itsmFortunaSilverService
    //                 ->convertirImagenesGlpiABase64(
    //                     $ticket['content'] ?? '',
    //                     $ticketId
    //                 ),

    //             'prioridad' => [
    //                 'id' => $ticket['priority'] ?? null,
    //                 'nombre' => $this->nombreNivelGlpi($ticket['priority'] ?? null),
    //             ],

    //             'urgencia' => [
    //                 'id' => $ticket['urgency'] ?? null,
    //                 'nombre' => $this->nombreNivelGlpi($ticket['urgency'] ?? null),
    //             ],

    //             'impacto' => [
    //                 'id' => $ticket['impact'] ?? null,
    //                 'nombre' => $this->nombreNivelGlpi($ticket['impact'] ?? null),
    //             ],
    //         ],

    //         'estado' => [
    //             'id' => $estadoId,
    //             'nombre' => $this->nombreEstadoGlpi($estadoId),
    //         ],

    //         'ultima_modificacion' => $ticket['date_mod'] ?? null,
    //         'fecha_ticket' => $ticket['date'] ?? null,

    //         'categoria' => [
    //             'id' => $ticket['itilcategories_id'] ?? null,
    //             'nombre' => null,
    //         ],

    //         'solicitante' => [
    //             'id' => $solicitanteId,
    //             'nombre' => $solicitanteNombre,
    //             'correo' => $solicitanteCorreo,
    //         ],

    //         'tecnico' => [
    //             'id' => $tecnicoId,
    //             'nombre' => $tecnicoNombre,
    //             'correo' => $tecnicoCorreo,
    //         ],

    //         'fecha_cierre' => $ticket['closedate'] ?? null,
    //     ];
    // }


    private function armarTicketCybernet(array $ticket): array
    {
        $ticketId = (int) ($ticket['id'] ?? 0);

        $solicitanteId = $ticket['users_id_recipient'] ?? null;
        $tecnicoId = $ticket['users_id_lastupdater'] ?? null;
        $estadoId = $ticket['status'] ?? null;

        $solicitante = $solicitanteId
            ? $this->itsmFortunaSilverService->obtenerUsuario((int) $solicitanteId)
            : null;

        $tecnico = $tecnicoId
            ? $this->itsmFortunaSilverService->obtenerUsuario((int) $tecnicoId)
            : null;

        $solicitanteNombre = trim(
            ($solicitante['firstname'] ?? '') . ' ' .
            ($solicitante['realname'] ?? '')
        );

        $tecnicoNombre = trim(
            ($tecnico['firstname'] ?? '') . ' ' .
            ($tecnico['realname'] ?? '')
        );

        $solicitanteCorreo = $solicitanteId
            ? $this->itsmFortunaSilverService->obtenerCorreoUsuario((int) $solicitanteId)
            : null;

        $tecnicoCorreo = $tecnicoId
            ? $this->itsmFortunaSilverService->obtenerCorreoUsuario((int) $tecnicoId)
            : null;

        /*
        |--------------------------------------------------------------------------
        | Obtener sede desde entities_id
        |--------------------------------------------------------------------------
        */
        $oficinaNombre = 'No identificada';
        $oficinaItsm = null;

        if (!empty($ticket['entities_id'])) {
            $oficinaItsm = OficinaItsm::where(
                'id_oficina_glpi',
                $ticket['entities_id']
            )->first();

            if ($oficinaItsm) {
                $oficinaNombre = Oficina::where('idOficina', $oficinaItsm->idOficina)
                    ->where('idCompania', 1007)
                    ->value('nombre') ?? 'No identificada';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Registrar solicitante GLPI en maePersona y asignarlo a oficina
        |--------------------------------------------------------------------------
        */
        $this->registrarPersonaGlpi(
            $solicitante ?? [],
            $solicitanteCorreo,
            $oficinaItsm
        );

        /*
        |--------------------------------------------------------------------------
        | Descripción original del ticket GLPI
        |--------------------------------------------------------------------------
        */
        $descripcionGlpi = $this->itsmFortunaSilverService
            ->convertirImagenesGlpiABase64(
                $ticket['content'] ?? '',
                $ticketId
            );

        /*
        |--------------------------------------------------------------------------
        | HTML final que irá al campo reporte
        |--------------------------------------------------------------------------
        */
        $reporteHtml = "
        <b>Solicitante:</b> {$solicitanteNombre}<br>
        <b>Correo:</b> {$solicitanteCorreo}<br>
        <b>Sede:</b> {$oficinaNombre}<br>
        <hr>
        {$descripcionGlpi}
    ";

        return [
            'ticket_id' => $ticketId,

            'titulo' => $ticket['name'] ?? null,

            'titulo_cybernet' =>
                'Ticket Ref. Fortuna Nro [' . $ticketId . '] - ' .
                ($ticket['name'] ?? ''),

            'detalle' => [
                'descripcion' => $reporteHtml,

                'prioridad' => [
                    'id' => $ticket['priority'] ?? null,
                    'nombre' => $this->nombreNivelGlpi($ticket['priority'] ?? null),
                ],

                'urgencia' => [
                    'id' => $ticket['urgency'] ?? null,
                    'nombre' => $this->nombreNivelGlpi($ticket['urgency'] ?? null),
                ],

                'impacto' => [
                    'id' => $ticket['impact'] ?? null,
                    'nombre' => $this->nombreNivelGlpi($ticket['impact'] ?? null),
                ],
            ],

            'estado' => [
                'id' => $estadoId,
                'nombre' => $this->nombreEstadoGlpi($estadoId),
            ],

            'ultima_modificacion' => $ticket['date_mod'] ?? null,
            'fecha_ticket' => $ticket['date'] ?? null,

            'categoria' => [
                'id' => $ticket['itilcategories_id'] ?? null,
                'nombre' => null,
            ],

            'solicitante' => [
                'id' => $solicitanteId,
                'nombre' => $solicitanteNombre,
                'correo' => $solicitanteCorreo,
            ],

            'tecnico' => [
                'id' => $tecnicoId,
                'nombre' => $tecnicoNombre,
                'correo' => $tecnicoCorreo,
            ],

            'fecha_cierre' => $ticket['closedate'] ?? null,
        ];
    }

    private function registrarPersonaGlpi(array $usuarioGlpi, ?string $correo, $oficinaItsm = null): ?Persona
    {
        if (empty($correo)) {
            return null;
        }

        $persona = Persona::firstOrCreate(
            [
                'usuario' => $correo,
            ],
            [
                'idPersonaNodo' => 'CYB',
                'idPersonaPerspectiva' => 'CYB',
                'nombre' => $usuarioGlpi['firstname'] ?? 'Sin nombre',
                'apellidos' => $usuarioGlpi['realname'] ?? '-',
                'flgEstado' => '1',
                'fechaRegistro' => now(),
                'fechaModificacion' => now(),
                'flgSyncHijo' => '0',
                'flgSyncPadre' => '0',
            ]
        );

        if ($oficinaItsm) {
            OficinaPersona::firstOrCreate(
                [
                    'idPersona' => $persona->idPersona,
                    'idPersonaNodo' => $persona->idPersonaNodo,
                    'idOficina' => $oficinaItsm->idOficina,
                    'idOficinaNodo' => 'CYB',
                ],
                [
                    'idOficinaPersonaNodo' => 'CYB',
                    'idNodoPerspectiva' => 'CYB',
                    'flgPrincipal' => '1',
                    'fechaInicio' => now(),
                    'flgEstado' => '1',
                    'fechaRegistro' => now(),
                    'fechaModificacion' => now(),
                    'flgSyncHijo' => '0',
                    'flgSyncPadre' => '0',
                ]
            );
        }

        return $persona;
    }
    // private function armarTicketCybernet(array $ticket): array
    // {
    //     $ticketId = (int) ($ticket['2'] ?? 0);
    //     $solicitanteId = $ticket['4'] ?? null;
    //     $tecnicoId = $ticket['5'] ?? null;
    //     $estadoId = $ticket['12'] ?? null;

    //     $solicitante = $solicitanteId
    //         ? $this->itsmFortunaSilverService->obtenerUsuario((int) $solicitanteId)
    //         : null;

    //     $tecnico = $tecnicoId
    //         ? $this->itsmFortunaSilverService->obtenerUsuario((int) $tecnicoId)
    //         : null;

    //     $ticketDetalle = $this->itsmFortunaSilverService
    //         ->obtenerDetalleTicket($ticketId);

    //     $solicitanteNombre = trim(
    //         ($solicitante['firstname'] ?? '') . ' ' .
    //         ($solicitante['realname'] ?? '')
    //     );

    //     $tecnicoNombre = trim(
    //         ($tecnico['firstname'] ?? '') . ' ' .
    //         ($tecnico['realname'] ?? '')
    //     );

    //     $solicitanteCorreo = $solicitanteId
    //         ? $this->itsmFortunaSilverService->obtenerCorreoUsuario((int) $solicitanteId)
    //         : null;

    //     $tecnicoCorreo = $tecnicoId
    //         ? $this->itsmFortunaSilverService->obtenerCorreoUsuario((int) $tecnicoId)
    //         : null;

    //     return [
    //         'ticket_id' => $ticketId,
    //         'titulo' => $ticket['1'] ?? null,
    //         'titulo_cybernet' =>
    //             'Ticket Ref. Fortuna Nro [' . $ticketId . '] - ' .
    //             ($ticket['1'] ?? ''),

    //         'detalle' => [
    //             'descripcion' => html_entity_decode(
    //                 strip_tags($ticketDetalle['content'] ?? '')
    //             ),

    //             'prioridad' => [
    //                 'id' => $ticketDetalle['priority'] ?? null,
    //                 'nombre' => $this->nombreNivelGlpi($ticketDetalle['priority'] ?? null),
    //             ],

    //             'urgencia' => [
    //                 'id' => $ticketDetalle['urgency'] ?? null,
    //                 'nombre' => $this->nombreNivelGlpi($ticketDetalle['urgency'] ?? null),
    //             ],

    //             'impacto' => [
    //                 'id' => $ticketDetalle['impact'] ?? null,
    //                 'nombre' => $this->nombreNivelGlpi($ticketDetalle['impact'] ?? null),
    //             ],
    //         ],

    //         'estado' => [
    //             'id' => $estadoId,
    //             'nombre' => $this->nombreEstadoGlpi($estadoId),
    //         ],

    //         'ultima_modificacion' => $ticket['19'] ?? null,
    //         'fecha_ticket' => $ticket['15'] ?? null,

    //         'categoria' => [
    //             'id' => 1,
    //             'nombre' => $ticket['7'] ?? null,
    //         ],

    //         'solicitante' => [
    //             'id' => $solicitanteId,
    //             'nombre' => $solicitanteNombre,
    //             'correo' => $solicitanteCorreo,
    //         ],

    //         'tecnico' => [
    //             'id' => $tecnicoId,
    //             'nombre' => $tecnicoNombre,
    //             'correo' => $tecnicoCorreo,
    //         ],

    //         'fecha_cierre' => $ticket['18'] ?? null,
    //     ];
    // }
}
