<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class BackupMonResumenController extends Controller
{
    public function Backup(Request $request)
    {
        // Obtener parámetros año y mes (ano sin tilde)
        $year = $request->query('ano', date('Y'));
        $month = str_pad($request->query('mes', date('m')), 2, '0', STR_PAD_LEFT);

        $startDate = "{$year}-{$month}-01 00:00:00";
        $endDate = date("Y-m-t 23:59:59", strtotime($startDate)); // Último día del mes

        $backupDir = "/var/backups_db_cyberline/monResumen";
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $fileName = "monResumen_backup_" . str_replace([' ', ':'], ['_', '-'], $startDate)
            . "_a_" . str_replace([' ', ':'], ['_', '-'], $endDate) . ".sql";
        $filePath = "{$backupDir}/{$fileName}";

        // Configuración DB remota (host, puerto, user, pass, base)
        $host = env('DB_PADRE_HOST', '127.0.0.1');
        $port = env('DB_PADRE_PORT', 3306);
        $db   = env('DB_PADRE_DATABASE', 'cyberline');
        $user = env('DB_PADRE_USERNAME', 'root');
        $pass = env('DB_PADRE_PASSWORD', '');

        // Condición para mysqldump y para eliminar registros
        $where = "fechaCreacion >= '{$startDate}' AND fechaCreacion <= '{$endDate}'";

        // Construir el comando mysqldump con host y puerto
        $cmd = "mysqldump -h{$host} -P{$port} -u{$user} -p{$pass} {$db} monResumen --where=\"{$where}\" > {$filePath} 2>&1";

        exec($cmd, $output, $status);

        if ($status === 0) {
            // Eliminar registros respaldados en la base local (ajustar conexión si es distinta)
            $deleted = DB::table('monResumen')
                ->whereBetween('fechaCreacion', [$startDate, $endDate])
                ->delete();

            return response()->json([
                'success'          => true,
                'mensaje'          => "Respaldo de {$year}-{$month} creado y registros eliminados.",
                'archivo'          => $filePath,
                'startDate'        => $startDate,
                'endDate'          => $endDate,
                'registros_borrados' => $deleted
            ]);
        } else {
            return response()->json([
                'success' => false,
                'mensaje' => "Error al crear el respaldo de {$year}-{$month}.",
                'comando' => $cmd,
                'status'  => $status,
                'output'  => $output
            ], 500);
        }
    }
}
