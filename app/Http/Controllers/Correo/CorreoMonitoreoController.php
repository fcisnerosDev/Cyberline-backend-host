<?php

namespace App\Http\Controllers\Correo;

use App\Http\Controllers\Controller;
use App\Models\ClienteCorreo;
use App\Models\SysNodo;

class CorreoMonitoreoController extends Controller
{
//     public function Correos()
// {
//     set_time_limit(120); // Establecer un límite de tiempo de ejecución de 120 segundos
//     $sysNodo = SysNodo::getSysNodo();
//     $dominio = 'correo.cyberline.com.pe'; // Dominio del servidor de correo
//     $localSmtp = trim($sysNodo['idNodo']) === 'CYB';

//     // Obtener credenciales del cliente por dominio
//     // $cliente = ClienteCorreo::where('idMaestroNodo', 'CYB')->first();

//     // if (!$cliente) {
//     //     return response()->json(['error' => 'No se encontraron credenciales para el dominio'], 404);
//     // }
//     // dd($cliente->nombre, $cliente->valor);
//     $username = "mon_mail@cyberline.com.pe";
//     $password = "Cyb3r_17";

//     // Utiliza novalidate-cert para no verificar el certificado SSL
//     $host = '{' . $dominio . ':993/imap/ssl/novalidate-cert}INBOX';

//     // Intentar conexión
//     $inbox = @imap_open($host, $username, $password);

//     if (!$inbox) {
//         return response()->json(['error' => imap_last_error(), 'imap_errors' => imap_errors()], 500);
//     }

//     // Si la conexión es exitosa
//     $mensajes = ['mensaje' => 'Conexión IMAP establecida correctamente'];

//     // Obtener todos los correos
//      $emails = imap_search($inbox, 'ALL');

//     if ($emails) {
//     rsort($emails); // Ordenar por fecha descendente

//      foreach ($emails as $num) {
//             $overview = imap_fetch_overview($inbox, $num, 0)[0];
//             $mensajes[] = [
//                 'asunto' => $overview->subject ?? '(Sin asunto)',
//                 'remitente' => $overview->from,
//                 'fecha' => $overview->date,
//             ];
//         }
//     }

//     imap_close($inbox);

//     return response()->json($mensajes);
// }

}
