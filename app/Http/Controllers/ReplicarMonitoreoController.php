<?php

namespace App\Http\Controllers;

use App\Models\Monitoreo;
use App\Models\MonitoreoSecundario;
use Illuminate\Support\Facades\DB;

class ReplicarMonitoreoController extends Controller
{
    public function replicar()
    {
        set_time_limit(0); // Sin límite de tiempo

        // Obtener los datos filtrados del modelo principal
        $monitoreos = Monitoreo::where('idNodoPerspectiva', 'AJI')->get();

        // Iniciar transacción para mayor eficiencia
        DB::beginTransaction();

        try {
            // Recorrer los datos y replicarlos en la base secundaria
            foreach ($monitoreos as $monitoreo) {
                // Replicar los datos en el modelo secundario
                MonitoreoSecundario::updateOrCreate(
                    ['idMonitoreo' => $monitoreo->idMonitoreo], // Clave única para evitar duplicados
                    $monitoreo->toArray() // Insertar o actualizar los datos
                );

                // Actualizar el campo flgSyncHijo a 1 en el modelo principal
                $monitoreo->flgSyncHijo = 1;
                $monitoreo->save(); // Guardar cambios en el modelo principal
            }

            // Commit después de cada ciclo de actualizaciones (al finalizar los ciclos)
            DB::commit();

            // Actualizar el campo flgSyncHijo a 1 en todos los registros de la base secundaria
            DB::beginTransaction(); // Nueva transacción para la actualización en la base secundaria
            MonitoreoSecundario::where('idNodoPerspectiva', 'AJI')->update(['flgSyncHijo' => 1]);
            DB::commit();

            return response()->json([
                'message' => 'Datos replicados y campo flgSyncHijo actualizado exitosamente',
                'total' => $monitoreos->count(),
            ]);
        } catch (\Exception $e) {
            // En caso de error, revertir la transacción
            DB::rollBack();

            return response()->json([
                'error' => 'Error en la replicación',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function replicaIndividual()
    {
        set_time_limit(0); // Sin límite de tiempo

        // Buscar el monitoreo específico en el modelo principal
        $monitoreo = Monitoreo::where('idNodoPerspectiva', "MIS")
            ->where('idMonitoreo', 16109)
            ->first();

        if (!$monitoreo) {
            return response()->json(['error' => 'Monitoreo no encontrado'], 404);
        }

        DB::beginTransaction();

        try {
            // Replicar el dato en el modelo secundario
            MonitoreoSecundario::updateOrCreate(
                ['idMonitoreo' => $monitoreo->idMonitoreo],
                $monitoreo->toArray()
            );

            // Actualizar el campo flgSyncHijo a 1 en el modelo principal
            $monitoreo->flgSyncHijo = 1;
            $monitoreo->save();

            // Actualizar el campo flgSyncHijo a 1 en el modelo secundario
            MonitoreoSecundario::where('idNodoPerspectiva', "MIS")
                ->where('idMonitoreo', 16109)
                ->update(['flgSyncHijo' => 1]);

            DB::commit();

            return response()->json([
                'message' => 'Monitoreo replicado exitosamente',
                'idMonitoreo' => 16109,
                'idNodoPerspectiva' => "MIS",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Error al replicar el monitoreo',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
