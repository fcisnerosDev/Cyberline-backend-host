<?php

namespace App\Exports;

use App\Models\Cliente\MonitoreoLocal;
use App\Models\Cliente\EquipoLocal;
use App\Models\Cliente\IpLocal;
use App\Models\Cliente\LogMonitoreoLocal;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteEstadosMonitoreoLocal implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return MonitoreoLocal::where('flgEstado', '1')
            ->where('flgStatus', '?')
            ->get()
            ->map(function ($monitoreo) {
                // Obtener el nombre del equipo
                $nombreEquipo = EquipoLocal::where('idEquipo', $monitoreo->idEquipo)->value('descripcion') ?? 'No disponible';

                // Obtener la dirección IP
                $direccionIp = IpLocal::where('idIp', $monitoreo->idIp)->value('ip') ?? 'No disponible';

                // Obtener el log más reciente del monitoreo
                $log = LogMonitoreoLocal::where('idMonitoreo', $monitoreo->idMonitoreo)
                    ->orderByDesc('fechaVerificacion')
                    ->first();

                // Extraer datos del log
                $comandoNagios = $log->command ?? 'No disponible';
                $estadoAlerta = ($monitoreo->flgStatus === '?') ? 'Desconocido' : ($log->flgStatus ?? 'No disponible');
                $ultimaEjecucion = $log->fechaVerificacion ?? 'No disponible';

                return [
                    'ID Monitoreo' => $monitoreo->idMonitoreo,
                    'Descripción Monitoreo' => $monitoreo->dscMonitoreo,
                    'Nombre del Equipo' => $nombreEquipo,
                    'Dirección IP' => $direccionIp,
                    'Comando de Nagios' => $comandoNagios,
                    'Estado de Alerta' => $estadoAlerta,
                    'Última Ejecución' => $ultimaEjecucion,
                ];
            });
    }


    public function headings(): array
    {
        return [
            'ID Monitoreo',
            'Descripción Monitoreo',
            'Nombre del Equipo',
            'Dirección IP',
            'Comando de Nagios',
            'Estado de Alerta',
            'Última Ejecución'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilos para encabezado
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], // Letras blancas
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '004592']],
        ]);

        // Autoajustar columnas
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return [];
    }
}
