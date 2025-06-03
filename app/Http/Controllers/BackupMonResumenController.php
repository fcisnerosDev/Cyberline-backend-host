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
        try {
            DB::connection()->getPdo(); // Validar conexión a DB
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión a la base de datos.',
                'error' => $e->getMessage(),
            ], 500);
        }

        $currentYear = Cache::get('backup_monresumen_year', 2023);
        if ($currentYear > 2024) {
            return response()->json(['message' => 'Todos los respaldos están completos hasta el año 2024.']);
        }

        $startDate = "{$currentYear}-01-01 00:00:00";
        $endDate   = "{$currentYear}-12-31 23:59:59";
        $backupDir = '/var/backups_db_cyberline/monResumen';

        // Validar existencia de ruta
        $rutaExiste = File::exists($backupDir);

        // Validar existencia de datos
        $dataCount = DB::table('monResumen')
            ->whereBetween('fechaCreacion', [$startDate, $endDate])
            ->count();

        return response()->json([
            'success' => true,
            'mensaje' => 'Validación completada',
            'año_actual' => $currentYear,
            'ruta_respaldo' => $backupDir,
            'ruta_existe' => $rutaExiste,
            'cantidad_registros_encontrados' => $dataCount,
        ]);
    }
}
