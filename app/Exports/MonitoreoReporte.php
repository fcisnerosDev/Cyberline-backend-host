<?php

namespace App\Exports;

use App\Models\Monitoreo;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class MonitoreoReporte implements FromCollection, WithHeadings, WithStyles
{
    protected $idOficinaPerspectiva;

    public function __construct($idOficinaPerspectiva = null)
    {
        $this->idOficinaPerspectiva = $idOficinaPerspectiva;
    }

    public function collection()
    {
        $query = Monitoreo::with(['equipo.oficina', 'Ip', 'servicio.maeMaestro', 'frecuencia'])
            ->where('flgEstado', '1');

        if ($this->idOficinaPerspectiva) {
            $query->whereHas('equipo.oficina', function ($q) {
                $q->where('idOficinaPerspectiva', $this->idOficinaPerspectiva)
                    ->where('idOficinaNodo', 'CYB');
            });
        }

        $monitoreos = $query->get();

        return $monitoreos->map(function ($mon) {
            $tiempoTranscurrido = Carbon::parse($mon->fechaUltimaVerificacion)
                ->diff(Carbon::now());
            $tiempoFormateado = sprintf(
                '%d:%02d:%02d',
                $tiempoTranscurrido->days * 24 + $tiempoTranscurrido->h,
                $tiempoTranscurrido->i,
                $tiempoTranscurrido->s
            );

            // Mapeo del estado de alerta
            $estadoAlerta = match ($mon->flgStatus) {
                'O' => 'OK',
                'C' => 'Crítico',
                'D' => 'Fuera de Monitoreo',
                'W' => 'Alerta',
                default => $mon->flgStatus,
            };

            return [
                'Compañia' => $mon->idNodoPerspectiva,
                'Equipo' => $mon->equipo->descripcion ?? '',
                'IP' => $mon->Ip->ip ?? '',
                'Servicio' => $mon->servicio->maeMaestro->nombre ?? '',
                'Frecuencia' => $mon->frecuencia->dscFrecuencia ?? '-',
                'Descripcion de Monitoreo' => $mon->dscMonitoreo,
                'Estado de alerta actual' => $estadoAlerta,
                'Tiempo Transcurrido' => $tiempoFormateado,
                'Última Verificación' => $mon->fechaUltimaVerificacion,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Compañia',
            'Equipo',
            'IP',
            'Servicio',
            'Frecuencia',
            'Descripcion de Monitoreo',
            'Estado de alerta actual',
            'Tiempo Transcurrido',
            'Última Verificación'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo cabecera
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '142C47'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Ajustar automáticamente ancho de columnas
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Colorear la columna G (Estado de alerta actual) según valor
        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $cell = 'G' . $row;
            $status = $sheet->getCell($cell)->getValue();
            $color = match ($status) {
                'OK' => '3DAB42',
                'Crítico' => 'DC3545',
                'Fuera de Monitoreo' => '337AB7',
                'Alerta' => 'FFC107',
                default => 'FFFFFF',
            };
            $sheet->getStyle($cell)
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB($color);
        }

        return $sheet;
    }
}
