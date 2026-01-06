<?php

namespace App\Console\Commands;

use App\Models\MonMonitoreoTelegram;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Monitoreo;
use Carbon\Carbon;

class TelegramStartCommand extends Command
{
    protected $signature = 'telegram:listen';
    protected $description = 'Envía alertas de monitoreo por Telegram';

    protected string $telegramToken;
    protected string $chatId;

    public function __construct()
    {
        parent::__construct();
        $this->telegramToken = env('TELEGRAM_BOT_TOKEN');
        $this->chatId = env('TELEGRAM_CHAT_ID');
    }

    public function handle()
    {
        $this->info("Bot de Telegram activo (solo alertas)");

        while (true) {
            $this->checkMonitoreoAndAlert();
            sleep(10);
        }
    }

    private function checkMonitoreoAndAlert(): void
    {
        // LIMPIEZA: eliminar registros de MonMonitoreoTelegram de monitoreos solucionados
        MonMonitoreoTelegram::whereHas('monitoreo', fn($q) => $q->where('flgStatus', 'O'))->delete();

        // Obtener monitoreos activos y agrupar por nodo
        $monitoreosPorNodo = Monitoreo::with(['equipo', 'ip'])
            ->where('flgEstado', '1')
            ->whereIn('flgStatus', ['C','?'])
            ->whereHas('equipo', fn($q) => $q->where('flgEstado', '1'))
            ->whereHas('ip', fn($q) => $q->where('flgEstado', '1'))
            ->whereHas('servicio', fn($q) => $q->where('flgEstado', '1'))
            ->whereHas('nodo', fn($q) => $q->where('alert_telegram', 1))
            ->get()
            ->groupBy('idNodoPerspectiva');

        $estados = ['C' => 'Crítico', '?' => 'Desconocido'];

        // Función para mostrar fecha y tiempo caído total
        $fechaLegible = function ($fecha) {
            if (!$fecha)
                return 'Sin fecha';

            $fechaCarbon = Carbon::parse($fecha)->timezone('America/Lima');
            $ahora = Carbon::now('America/Lima');

            // Total de segundos desde fechaUltimoCambio hasta ahora
            $totalSegundos = $ahora->timestamp - $fechaCarbon->timestamp;

            $dias = floor($totalSegundos / 86400);
            $resto = $totalSegundos % 86400;

            $horas = floor($resto / 3600);
            $resto = $resto % 3600;

            $minutos = floor($resto / 60);
            $segundos = $resto % 60;

            $partes = [];
            if ($dias > 0)
                $partes[] = "{$dias}d";
            if ($horas > 0)
                $partes[] = "{$horas}h";
            if ($minutos > 0)
                $partes[] = "{$minutos}m";
            if ($dias == 0 && $horas == 0 && $minutos == 0)
                $partes[] = "{$segundos}s";

            $tiempoCaido = implode(' ', $partes);

            return $fechaCarbon->format('d/m/Y H:i') . " (Caído: {$tiempoCaido})";
        };

        foreach ($monitoreosPorNodo as $idNodoPerspectiva => $items) {

            // Filtrar solo los items que deben notificarse (anti-spam)
            $itemsParaNotificar = $items->filter(function ($item) use ($idNodoPerspectiva) {
                $registroTelegram = MonMonitoreoTelegram::firstOrCreate([
                    'idMonitoreo' => $item->idMonitoreo,
                    'idMonitoreoNodo' => $item->idMonitoreoNodo,
                    'idNodoPerspectiva' => $idNodoPerspectiva,
                ]);

                if (
                    $registroTelegram->last_notified_at &&
                    Carbon::parse($registroTelegram->last_notified_at)->diffInMinutes(now()) < 60
                ) {
                    return false;
                }

                $item->registroTelegram = $registroTelegram;
                return true;
            });

            if ($itemsParaNotificar->isEmpty())
                continue;

            $mensaje = '';

            if ($itemsParaNotificar->count() === 1) {
                $item = $itemsParaNotificar->first();
                $mensaje = "🚨 ALERTA - {$idNodoPerspectiva}\n"
                    . "Equipo: {$item->equipo->descripcion} - IP: {$item->ip->ip}\n"
                    . "Descripción: {$item->dscMonitoreo}\n"
                    . "Estado: " . ($estados[$item->flgStatus] ?? $item->flgStatus) . "\n"
                    . "Último cambio: " . $fechaLegible($item->fechaUltimoCambio) . "\n"
                    . str_repeat('─', 20);

                $item->registroTelegram->update(['last_notified_at' => now()]);

            } else {
                $mensaje = "🚨 ALERTA - {$itemsParaNotificar->count()} INCIDENTES\n"
                    . "Nodo: {$idNodoPerspectiva}\n"
                    . str_repeat('─', 20) . "\n";

                foreach ($itemsParaNotificar as $item) {
                    $mensaje .= "• Equipo: {$item->equipo->descripcion} - IP: {$item->ip->ip}\n"
                        . "  Descripción: {$item->dscMonitoreo}\n"
                        . "  Estado: " . ($estados[$item->flgStatus] ?? $item->flgStatus) . "\n"
                        . "  Último cambio: " . $fechaLegible($item->fechaUltimoCambio) . "\n\n";

                    $item->registroTelegram->update(['last_notified_at' => now()]);
                }

                $mensaje .= str_repeat('─', 30);
            }

            $this->sendMessage($mensaje);
        }
    }

    private function sendMessage(string $message): void
    {
        Http::get(
            "https://api.telegram.org/bot{$this->telegramToken}/sendMessage",
            [
                'chat_id' => $this->chatId,
                'text' => $message
            ]
        );
    }
}
