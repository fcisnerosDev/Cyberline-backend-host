<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\SysNodo;

class InformarConexionController extends Controller
{
    public function sincronizarConHijo()
    {
        $nodo = $this->obtenerNodo();
        if (!$nodo) return;

        $ip = trim($nodo->ip);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            error_log("[ERROR] IP no válida: {$ip}");
            return;
        }

        error_log("[INFO] Realizando ping a hijo: {$ip}");
        $isAlive = $this->Ping($ip);
        error_log("[INFO] Resultado del ping a hijo: " . ($isAlive ? "1" : "0"));

        $nuevoFlgSyncHijo = $isAlive ? "1" : "0";

        // Si el nodo está activo, siempre actualizamos fechaSyncHijo
        $updateData = ['flgSyncHijo' => $nuevoFlgSyncHijo];
        if ($isAlive) {
            $updateData['fechaSyncHijo'] = Carbon::now(); // Siempre se actualiza
        }

        DB::connection('mysql_padre')->table('sysNodo')
            ->where('idNodo', 'CLNT')
            ->update($updateData);

        error_log("[INFO] flgSyncHijo actualizado a: {$nuevoFlgSyncHijo}");
        error_log("[INFO] fechaSyncHijo actualizada a: " . Carbon::now());

        $this->actualizarConexionNodo();
    }


    public function sincronizarConPadre()
    {
        $nodo = $this->obtenerNodo();
        if (!$nodo) return;

        $dbHost = trim(env('DB_PADRE_HOST'));
        if (!filter_var($dbHost, FILTER_VALIDATE_IP)) {
            error_log("[ERROR] IP inválida en DB_PADRE_HOST: {$dbHost}");
            return;
        }

        error_log("[INFO] Realizando ping a padre: {$dbHost}");
        $isAlive = $this->Ping($dbHost);
        error_log("[INFO] Resultado del ping a padre: " . ($isAlive ? "1" : "0"));

        $nuevoFlgSyncPadre = $isAlive ? "1" : "0";

        // Siempre actualizar fechaSyncPadre si está en línea
        $updateData = ['flgSyncPadre' => $nuevoFlgSyncPadre];
        if ($isAlive) {
            $updateData['fechaSyncPadre'] = Carbon::now(); // Siempre actualiza la fecha
        }

        DB::connection('mysql_padre')->table('sysNodo')
            ->where('idNodo', 'CLNT')
            ->update($updateData);

        error_log("[INFO] flgSyncPadre actualizado a: {$nuevoFlgSyncPadre}");
        if ($isAlive) {
            error_log("[INFO] fechaSyncPadre actualizada a: " . Carbon::now());
        }

        $this->actualizarConexionNodo();
    }


    private function actualizarConexionNodo()
    {
        $nodo = DB::connection('mysql_padre')->table('sysNodo')
            ->where('idNodo', 'CLNT')
            ->first();

        if (!$nodo) {
            var_dump("Nodo no encontrado.");
            return;
        }

        $flgSyncHijo = ($nodo->flgSyncHijo == "1") ? "1" : "0";
        $flgSyncPadre = ($nodo->flgSyncPadre == "1") ? "1" : "0";
        $nuevoFlgConexion = ($flgSyncHijo === "1" && $flgSyncPadre === "1") ? "1" : "0";

        if ($nuevoFlgConexion === "1" && $nodo->flgConexion !== "1") {
            DB::connection('mysql_padre')->table('sysNodo')
                ->where('idNodo', 'CLNT')
                ->update([
                    'flgConexion' => "1",
                    'fechaVerificacionMonitoreo' => Carbon::now(),
                    'fechaConexion' => Carbon::now(),
                    'mensajeMonitoreo' => $this->determinarMensajeMonitoreo($flgSyncHijo, $flgSyncPadre)
                ]);
        } elseif ($nuevoFlgConexion === "0" && $nodo->flgConexion === "1") {
            DB::connection('mysql_padre')->table('sysNodo')
                ->where('idNodo', 'CLNT')
                ->update([
                    'flgConexion' => "0",
                    'mensajeMonitoreo' => $this->determinarMensajeMonitoreo($flgSyncHijo, $flgSyncPadre)
                ]);
        }
    }

    private function determinarMensajeMonitoreo($flgSyncHijo, $flgSyncPadre)
    {
        if ($flgSyncHijo === "0" && $flgSyncPadre === "0") {
            return "No hay conexión en ambos sentidos.";
        } elseif ($flgSyncHijo === "0") {
            return "No hay conexión desde el nodo padre al nodo hijo.";
        } elseif ($flgSyncPadre === "0") {
            return "No hay conexión desde el nodo hijo al nodo padre.";
        }
        return "Conexión establecida correctamente.";
    }

    private function obtenerNodo()
    {
        return SysNodo::where('idNodo', 'CLNT')->where('flgEstado', "1")->first();
    }

    public function replicarDatosConexion()
    {
        $idNodoHijo = env('ID_NODO_HIJO'); // Cada hijo toma su propio ID desde su .env

        // Obtener datos del nodo padre
        $nodoPadre = DB::connection('mysql_padre')->table('sysNodo')
            ->where('idNodo', 'CLNT') // ID del nodo padre
            ->first();

        if (!$nodoPadre || $nodoPadre->flgSyncHijo != "1" || $nodoPadre->flgSyncPadre != "1") {
            error_log("[INFO] No hay conexión entre ambos nodos, no se replica.");
            return;
        }

        error_log("[INFO] Replicando datos de sysNodo del idNodo: {$idNodoHijo} desde el Padre...");

        // Obtener los datos del idNodo hijo desde el padre
        $datoPadre = DB::connection('mysql_padre')->table('sysNodo')
            ->where('idNodo', $idNodoHijo)
            ->first();

        if (!$datoPadre) {
            error_log("[INFO] No se encontró el idNodo {$idNodoHijo} en la BD padre.");
            return;
        }

        // Insertar o actualizar el nodo en la BD del hijo
        DB::table('sysNodo')->updateOrInsert(
            ['idNodo' => $datoPadre->idNodo], // Clave primaria
            [
                'nombre' => $datoPadre->nombre,
                'ip' => $datoPadre->ip,
                'flgConexion' => $datoPadre->flgConexion,
                'flgSyncHijo' => $datoPadre->flgSyncHijo,
                'fechaSyncHijo'=> $datoPadre->fechaSyncHijo,
                'flgSyncPadre' => $datoPadre->flgSyncPadre,
                'fechaSyncPadre'=> $datoPadre->fechaSyncPadre,
                'fechaVerificacionMonitoreo' => $datoPadre->fechaVerificacionMonitoreo,
                'fechaConexion' => $datoPadre->fechaConexion,
                'mensajeMonitoreo' => $datoPadre->mensajeMonitoreo,

            ]
        );

        error_log("[INFO] Datos del idNodo {$idNodoHijo} replicados correctamente.");
    }

    private function Ping($ip)
    {
        $pingCommand = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            ? "ping -n 3 -w 2000 " . escapeshellarg($ip)
            : "ping -c 3 -W 2 " . escapeshellarg($ip);

        $output = shell_exec($pingCommand);
        if ($output === null) {
            error_log("[ERROR] No se pudo ejecutar el comando de ping.");
            return false;
        }

        return preg_match('/(\d+) received/', $output, $matches) && intval($matches[1]) > 0;
    }
}
