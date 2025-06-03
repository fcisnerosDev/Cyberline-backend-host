<?php

namespace App\Http\Controllers;

use App\Exports\ReporteEstadosMonitoreoLocal;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ReportMonitoreoCliente extends Controller
{
    public function exportarReporteExcelMonitoreo()
    {
        $idNodoHijo = env('ID_NODO_HIJO', 'desconocido');
        $fechaActual = now()->format('d-m-Y');
        $horaActual = now()->format('H\h i');

        // Formato corregido sin espacio en la hora
        $horaActual = str_replace(' ', '', $horaActual);

        $nombreArchivo = "reporte-monitoreos-desconocidos-{$idNodoHijo}-{$fechaActual}-{$horaActual}.xlsx";

        return Excel::download(new ReporteEstadosMonitoreoLocal(), $nombreArchivo);
    }
}
