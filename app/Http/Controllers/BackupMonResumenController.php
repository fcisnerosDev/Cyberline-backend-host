<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class BackupMonResumenController extends Controller
{
    public function Backup()
    {
        $startDate = '2023-01-01 00:00:00';
        $endDate   = '2023-01-31 23:59:59';

        $backupDir = "/var/backups_db_cyberline/monResumen";
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $fileName = "monResumen_backup_2023_01.sql";
        $filePath = "{$backupDir}/{$fileName}";

        // Datos desde .env
        $db   = config('database.connections.mysql.database');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        $where = "fechaCreacion >= '{$startDate}' AND fechaCreacion <= '{$endDate}'";

        $cmd = "mysqldump -u{$user} -p{$pass} -h{$host} {$db} monResumen --where=\"{$where}\" > {$filePath}";

        exec($cmd, $output, $status);

        if ($status === 0) {
            // Eliminar registros después del respaldo
            $deleted = DB::table('monResumen')
                ->whereBetween('fechaCreacion', [$startDate, $endDate])
                ->delete();

            return response()->json([
                'success'      => true,
                'mensaje'      => 'Respaldo de enero 2023 creado y registros eliminados.',
                'archivo'      => $filePath,
                'startDate'    => $startDate,
                'endDate'      => $endDate,
                'registros_borrados' => $deleted
            ]);
        } else {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al crear el respaldo de enero 2023.',
                'comando' => $cmd,
                'status'  => $status,
                'output'  => $output
            ], 500);
        }
    }
}
