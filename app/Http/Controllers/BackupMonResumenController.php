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
    $endDate = date("Y-m-t 23:59:59", strtotime($startDate));

    $backupDir = "/var/backups_db_cyberline/monResumen";
    if (!File::exists($backupDir)) {
        File::makeDirectory($backupDir, 0755, true);
    }

    $fileName = "monResumen_backup_" . str_replace([' ', ':'], ['_', '-'], $startDate) . "_a_" . str_replace([' ', ':'], ['_', '-'], $endDate) . ".sql";
    $filePath = "{$backupDir}/{$fileName}";

    $db   = config('database.connections.mysql.database');
    $user = config('database.connections.mysql.username');
    $pass = config('database.connections.mysql.password');
    $host = config('database.connections.mysql.host');

    $where = "fechaCreacion >= '{$startDate}' AND fechaCreacion <= '{$endDate}'";

    $cmd = "mysqldump -u{$user} -p{$pass} {$db} monResumen --where=\"{$where}\" > {$filePath}";

    exec($cmd, $output, $status);

    if ($status === 0) {
        $deleted = DB::table('monResumen')
            ->whereBetween('fechaCreacion', [$startDate, $endDate])
            ->delete();

        return response()->json([
            'success'         => true,
            'mensaje'         => "Respaldo de {$year}-{$month} creado y registros eliminados.",
            'archivo'         => $filePath,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
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
