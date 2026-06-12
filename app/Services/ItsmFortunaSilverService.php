<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ItsmFortunaSilverService
{
    private $url;
    private $appToken;
    private $userToken;
    private $urlIp;

    public function __construct()
    {
        $this->url = env('ITSM_FORTUNA_SILVER_URL');
        $this->appToken = env('ITSM_FORTUNA_SILVER_APP_TOKEN');
        $this->userToken = env('ITSM_FORTUNA_SILVER_USER_TOKEN');
        $this->urlIp = env('ITSM_FORTUNA_SILVER_URL_IP');
    }

    // INIT SESSION
    public function initSession()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "user_token {$this->userToken}",
                'App-Token' => $this->appToken,
                'Accept' => 'application/json',
            ])->get($this->url . '/initSession');

            if (!$response->successful()) {
                throw new \Exception("Error GLPI: " . $response->body());
            }

            $data = $response->json();

            if (!isset($data['session_token'])) {
                throw new \Exception("Respuesta sin session_token: " . $response->body());
            }

            Cache::put(
                'itsm_fortuna_silver_session_token',
                $data['session_token'],
                now()->addMinutes(50)
            );

            return $data['session_token'];

        } catch (\Exception $e) {
            throw new \Exception("Error en initSession: " . $e->getMessage());
        }
    }

    // GET SESSION TOKEN
    public function getSessionToken()
    {
        return Cache::remember(
            'itsm_fortuna_silver_session_token',
            now()->addMinutes(50),
            function () {
                return $this->initSession();
            }
        );
    }

    // CREATE TICKET
    public function createTicketFortunaITSM($ticket)
    {
        $sessionToken = $this->getSessionToken();

        $payload = [
            'input' => [
                'name' => $ticket['asunto'] ?? 'Sin asunto',
                'content' => $ticket['descripcion'] ?? 'Sin descripcion',
                'date' => $ticket['fechaCreacion'] ?? now()->format('Y-m-d H:i:s'),
                'itilcategories_id' => 81,
                '_users_id_requester' => 8,
                'urgency' => 3,
                'impact' => 3,
                '_users_id_assign' => 7,
                'priority' => 3,
            ]
        ];

        $response = Http::withHeaders([
            'Session-Token' => $sessionToken,
            'App-Token' => $this->appToken,
            'Content-Type' => 'application/json',
        ])->post($this->url . '/Ticket', $payload);

        if ($response->status() == 401) {
            Cache::forget('itsm_fortuna_silver_session_token');
            $this->initSession();
            return $this->createTicketFortunaITSM($ticket);
        }

        if (!$response->successful()) {
            throw new \Exception("Error creando ticket ITSM: " . $response->body());
        }

        return $response->json();
    }

    // MAPEO DE ESTADOS
    private function mapStatusToGLPI($flgStatus)
    {
        return match ((int) $flgStatus) {
            0 => 2, // Asignado -> En curso
            3 => 2, // En progreso -> En curso
            6 => 4, // Pendiente -> En espera
            2 => 5, // Resuelto -> Resuelto
            1 => 6, // Cerrado -> Cerrado
            default => 2,
        };
    }

    // UPDATE STATUS
    public function updateTicketStatus($ticketIdItsm, $status, $fechaAtencion = null, $fechaCierre = null)
    {
        $sessionToken = $this->getSessionToken();

        $input = [
            'status' => $status
        ];

        //  RESUELTO → solvedate
        if ($status == 4 && $fechaAtencion) {
            $input['solvedate'] = \Carbon\Carbon::parse($fechaAtencion)->format('Y-m-d H:i:s');
        }

        //  CERRADO → solvedate
        if ($status == 5 && $fechaCierre) {
            $input['solvedate'] = \Carbon\Carbon::parse($fechaCierre)->format('Y-m-d H:i:s');
        }

        $payload = [
            'input' => $input
        ];
        // dd($payload);
        $response = Http::withHeaders([
            'Session-Token' => $sessionToken,
            'App-Token' => $this->appToken,
            'Content-Type' => 'application/json',
        ])->put($this->url . "/Ticket/{$ticketIdItsm}", $payload);

        if ($response->status() == 401) {
            Cache::forget('itsm_fortuna_silver_session_token');
            $this->initSession();
            return $this->updateTicketStatus($ticketIdItsm, $status, $fechaAtencion, $fechaCierre);
        }

        if (!$response->successful()) {
            throw new \Exception("Error actualizando estado ITSM: " . $response->body());
        }

        return $response->json();
    }


    public function addFollowup($ticketIdItsm, $content)
    {
        $sessionToken = $this->getSessionToken();

        $payload = [
            'input' => [
                'items_id' => $ticketIdItsm,
                'itemtype' => 'Ticket',
                'content' => $content ?? '<p>Sin detalle</p>',
                'is_private' => false
            ]
        ];

        // dd($payload);

        // \Log::info('ITSM FOLLOWUP SEND', $payload);

        $response = Http::withHeaders([
            'Session-Token' => $sessionToken,
            'App-Token' => $this->appToken,
            'Content-Type' => 'application/json',
        ])->post($this->url . '/ITILFollowup', $payload);

        if ($response->status() == 401) {
            Cache::forget('itsm_fortuna_silver_session_token');
            $this->initSession();
            return $this->addFollowup($ticketIdItsm, $content);
        }

        if (!$response->successful()) {
            throw new \Exception("Error creando followup ITSM: " . $response->body());
        }

        return $response->json();
    }


    // SYNC STATUS DESDE TU SISTEMA
    public function syncStatusFromLocal($ticketIdItsm, $flgStatus, $fechaAtencion = null, $fechaCierre = null)
    {
        $statusGLPI = $this->mapStatusToGLPI($flgStatus);

        return $this->updateTicketStatus(
            $ticketIdItsm,
            $statusGLPI,
            $fechaAtencion,
            $fechaCierre
        );
    }

    public function ticketsActivos()
    {
        $sessionToken = $this->initSession();
        // dd($sessionToken);

        $response = Http::withHeaders([
            'Session-Token' => $sessionToken,
            'App-Token' => $this->appToken,
            'Accept' => 'application/json',
        ])->get($this->url . '/Ticket/');

        if (!$response->successful()) {
            return [
                'estado' => false,
                'message' => 'No se pudieron obtener los tickets',
                'error' => $response->json(),
            ];
        }

        $tickets = collect($response->json())
            ->filter(function ($ticket) {
                return isset($ticket['status']) && (int) $ticket['status'] !== 6;
            })
            ->values();

        return [
            'estado' => true,
            'data' => $tickets,
        ];
    }
    public function obtenerUsuario(int $userId)
    {
        $sessionToken = $this->initSession();

        $response = Http::withHeaders([
            'Session-Token' => $sessionToken,
            'App-Token' => $this->appToken,
        ])->get($this->url . "/User/{$userId}");

        if (!$response->successful()) {
            return [];
        }

        return $response->json();
    }

    public function obtenerCorreoUsuario(int $userId): ?string
    {
        $sessionToken = $this->initSession();

        $response = Http::withHeaders([
            'Session-Token' => $sessionToken,
            'App-Token' => $this->appToken,
        ])->get($this->url . "/User/{$userId}/UserEmail");

        if (!$response->successful()) {
            return null;
        }

        $emails = collect($response->json());

        $emailDefault = $emails->firstWhere('is_default', 1);

        return $emailDefault['email']
            ?? $emails->first()['email']
            ?? null;
    }

    public function obtenerDetalleTicket(int $ticketId)
    {
        $sessionToken = $this->initSession();

        $response = Http::withHeaders([
            'Session-Token' => $sessionToken,
            'App-Token' => $this->appToken,
        ])->get($this->url . "/Ticket/{$ticketId}");

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    public function convertirImagenesGlpiABase64(string $html, int $ticketId): string
{
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    preg_match_all(
        '/<img[^>]*src=["\']([^"\']*docid=([0-9]+)[^"\']*)["\'][^>]*>/i',
        $html,
        $matches,
        PREG_SET_ORDER
    );

    foreach ($matches as $match) {
        $srcOriginal = $match[1];
        $docId = (int) $match[2];

        $documentResponse = Http::withHeaders([
            'Session-Token' => $this->getSessionToken(),
            'App-Token' => $this->appToken,
        ])->get($this->url . "/Document/{$docId}");

        if (!$documentResponse->successful()) {
            continue;
        }

        $document = $documentResponse->json();
        $mime = $document['mime'] ?? 'image/png';

        if (!str_starts_with($mime, 'image/')) {
            continue;
        }

        $downloadUrl = rtrim($this->urlIp, '/')
            . "/front/document.send.php?docid={$docId}&itemtype=Ticket&items_id={$ticketId}";

        $imageResponse = Http::withHeaders([
            'Session-Token' => $this->getSessionToken(),
            'App-Token' => $this->appToken,
        ])->get($downloadUrl);

        if (!$imageResponse->successful()) {
            \Log::warning('No se pudo descargar imagen GLPI', [
                'docid' => $docId,
                'ticket_id' => $ticketId,
                'url' => $downloadUrl,
                'status' => $imageResponse->status(),
            ]);
            continue;
        }

        $base64 = 'data:' . $mime . ';base64,' . base64_encode($imageResponse->body());

        $html = str_replace($srcOriginal, $base64, $html);
    }

    return $html;
}
}