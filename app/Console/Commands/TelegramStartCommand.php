<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Hash;
use App\Models\UserCyberV6;
use App\Models\Monitoreo;
use App\Models\UserSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramStartCommand extends Command
{
    protected $signature = 'telegram:listen';
    protected $description = 'Escucha mensajes del bot de Telegram';
    protected $telegramToken;
    protected $lastUpdateId = 0;

    public function __construct()
    {
        parent::__construct();
        $this->telegramToken = env('TELEGRAM_BOT_TOKEN');
    }

    public function handle()
    {
        $this->info("Escuchando mensajes de Telegram...");

        while (true) {
            $this->processTelegramUpdates();
            $this->checkMonitoreoAndAlert();
            usleep(500000); // Reduce el uso de recursos en lugar de sleep(30)
        }
    }

    private function processTelegramUpdates()
    {
        $response = Http::get("https://api.telegram.org/bot{$this->telegramToken}/getUpdates", [
            'offset' => $this->lastUpdateId + 1,
        ]);

        $updates = $response->json();

        if (isset($updates['result']) && count($updates['result']) > 0) {
            foreach ($updates['result'] as $update) {
                $this->lastUpdateId = $update['update_id'];
                if (isset($update['message']['text'])) {
                    $chatId = $update['message']['chat']['id'];
                    $text = trim($update['message']['text']);
                    $this->handleUserMessage($chatId, $text);
                }
            }
        }
    }

    private function handleUserMessage($chatId, $text)
    {
        if ($text === '/start') {
            $this->sendMessage($chatId, "¡Hola! Soy tu bot de Cyberline 🤖. Para continuar, ingresa tu usuario:");
        } else {
            $this->authenticateUser($chatId, $text);
        }
    }

    private function authenticateUser($chatId, $input)
    {
        // Verificar si la sesión ya existe en la caché o base de datos
        $userSession = Cache::get("user_session_{$chatId}", function () use ($chatId) {
            // Si no existe en la caché, buscar en la base de datos
            $session = UserSession::where('chat_id', $chatId)->first();
            if ($session) {
                return [
                    'step' => $session->step,
                    'attempts' => $session->attempts,
                    'user' => UserCyberV6::find($session->user_idPersona), // Recuperamos el usuario si ya existe la sesión
                ];
            }
            // Si no existe, inicializamos una nueva sesión
            return ['step' => 'username', 'attempts' => 0];
        });

        if ($userSession['step'] === 'username') {
            $inputTrimmed = trim($input);  // Limpiar espacios
            $user = UserCyberV6::where('usuario', $inputTrimmed)->first();

            if (!$user) {
                $this->sendMessage($chatId, "Usuario no encontrado. Inténtalo de nuevo.");
                return;
            }
            if ($user->bloqueado) {
                $this->sendMessage($chatId, "Tu cuenta está bloqueada. Contacta al área de desarrollo de Cyberline.");
                return;
            }

            // Actualizar la sesión en la base de datos y la caché
            $userSession['user'] = $user;
            $userSession['step'] = 'password';
            Cache::forever("user_session_{$chatId}", $userSession);

            // No guardamos idPersona todavía, solo guardamos el paso actual y el estado
            UserSession::updateOrCreate(
                ['chat_id' => $chatId],
                ['step' => 'password', 'attempts' => 0] // Sin idPersona aún
            );

            $this->sendMessage($chatId, "Ahora ingresa tu contraseña:");
            return;
        }

        if ($userSession['step'] === 'password') {
            $user = $userSession['user'];

            if (Hash::check($input, $user->password)) {
                $user->update(['intentos_fallidos' => 0]);
                Cache::put("authenticated_users_{$chatId}", $user->idTelegram, now()->addMinutes(60));

                // Ahora que la autenticación fue exitosa, guardamos el idPersona en la sesión
                UserSession::where('chat_id', $chatId)->update([
                    'user_idPersona' => $user->idPersona,  // Guardamos el idPersona del usuario autenticado
                    'authenticated' => true,  // Marcamos la sesión como autenticada
                ]);

                $this->sendMessage($chatId, "✅ Autenticación exitosa. Bienvenido, {$user->nombre}.");

                // Limpiar la sesión tanto de la caché como de la base de datos (ya está autenticado)
                Cache::forget("user_session_{$chatId}");
                return;
            }

            $user->increment('intentos_fallidos');
            if ($user->intentos_fallidos >= 3) {
                $user->update(['bloqueado' => true]);
                $this->sendMessage($chatId, "❌ Has excedido el número de intentos. Tu cuenta ha sido bloqueada. Contacta al área de desarrollo de Cyberline.");

                Cache::forget("user_session_{$chatId}");
                UserSession::where('chat_id', $chatId)->delete();
                return;
            }

            $remainingAttempts = 3 - $user->intentos_fallidos;
            Cache::forever("user_session_{$chatId}", $userSession);
            $this->sendMessage($chatId, "❌ Contraseña incorrecta. Intentos restantes: {$remainingAttempts}");
        }
    }














    // private function checkMonitoreoAndAlert()
    // {
    //     try {
    //         $this->info("Verificando monitoreos críticos...");

    //         // $monitoreos = Monitoreo::where('flgEstado', 1)
    //         //     ->whereIn('flgStatus', ['C'])
    //         //     ->where('dscMonitoreo', 'VPN AMAZON - 192.168.143.14') // Filtro específico para la VPN
    //         //     ->get();

    //         $monitoreos = Monitoreo::where('flgEstado', 1)
    //             ->whereIn('flgStatus', ['C'])
    //             ->get();

    //         if ($monitoreos->isEmpty()) {
    //             $this->info("No hay alertas críticas ");
    //             return;
    //         }

    //         foreach ($monitoreos as $monitoreo) {
    //             $this->info("Alerta detectada: {$monitoreo->dscMonitoreo} - Estado: {$monitoreo->flgStatus}");
    //             $this->sendAlertToAuthenticatedUsers($monitoreo);
    //         }
    //     } catch (\Exception $e) {
    //         $this->error("Error en checkMonitoreoAndAlert: " . $e->getMessage());
    //     }
    // }


    private function checkMonitoreoAndAlert()
{
    try {
        $this->info("Verificando monitoreos críticos...");

        $monitoreos = Monitoreo::where('flgEstado', 1)
            ->whereIn('flgStatus', ['C'])
            ->get();

        if ($monitoreos->isEmpty()) {
            $this->info("No hay alertas críticas");
            return;
        }

        foreach ($monitoreos as $monitoreo) {
            $this->info("Alerta detectada: {$monitoreo->dscMonitoreo} - Estado: {$monitoreo->flgStatus}");
            $this->sendAlertToAuthenticatedUsers($monitoreo);
        }

    } catch (\Exception $e) {
        $this->error("Error en checkMonitoreoAndAlert: " . $e->getMessage());
    }
}

private function sendAlertToAuthenticatedUsers($monitoreo)
{
    // Obtener los usuarios autenticados con authenticated = 1
    $authenticatedUsers = UserSession::where('authenticated', 1)->get();

    if ($authenticatedUsers->isEmpty()) {
        $this->info("No hay usuarios autenticados.");
        return; // Salir si no hay usuarios
    }

    // Enviar la alerta a cada usuario autenticado uno por uno
    foreach ($authenticatedUsers as $user) {
        $this->sendMessage($user->chat_id, "🚨 Alerta: {$monitoreo->dscMonitoreo} está en estado crítico ({$monitoreo->flgStatus}). ¡Requiere atención inmediata!");

        // Esperar un minuto antes de enviar el siguiente mensaje
        sleep(60); // Retraso de 1 minuto
    }
}

//     private function sendAlertToAuthenticatedUsers($monitoreo)
// {
//     // Obtener los usuarios autenticados con authenticated = 1
//     $authenticatedUsers = UserSession::where('authenticated', 1)->get();

//     if ($authenticatedUsers->isEmpty()) {
//         $this->info("No hay usuarios autenticados.");
//         return; // Puedes agregar un return aquí para salir del método si no hay usuarios
//     }

//     // Enviar la alerta a cada usuario autenticado
//     foreach ($authenticatedUsers as $user) {
//         $this->sendMessage($user->chat_id, "🚨 Alerta: {$monitoreo->dscMonitoreo} está en estado crítico ({$monitoreo->flgStatus}). ¡Requiere atención inmediata!");
//     }
// }

    private function sendMessage($chatId, $message)
    {
        Http::get("https://api.telegram.org/bot{$this->telegramToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message
        ]);
    }
}
