<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FacturaExportDemo implements FromCollection, WithHeadings, WithStyles
{
    private $facturas;

    public function __construct($facturas)
    {
        $this->facturas = $facturas;
    }

    public function collection()
    {
        return collect($this->facturas);
    }

    public function headings(): array
    {
        return [
            'ID Factura',
            'N° de Factura',
            'Fecha Factura',
            'Compañia' ,
            'Razón Social' ,
            'RUC' ,
            'Moneda',
            'Tipo de Cambio',
            'Valor en soles',
            'Valor en Dolares',
            'Condición de Pago' ,
            'Marca',
            'Referencia',
            'Régimen Tributario',
            'Tipo de Servicio',
            'Descripción Servicio',
            'Descripción Agregada',
            'Monto',
            'Cantidad',
            'Precio Unitario',
            'Subtotal',
            'IGV',
            'Total',
        ];
    }

    public function styles(Worksheet $sheet)
{
    // Aplicar estilo a la fila de encabezado
    $sheet->getStyle('A1:W1')->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'], // Blanco
        ],
        'fill' => [
            'fillType' => 'solid',
            'startColor' => ['rgb' => '0000FF'], // Azul
        ],
    ]);

    // Ajustar automáticamente el ancho de las columnas
    foreach (range('A', 'T') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    foreach ($this->facturas as $index => $factura) {
        $row = $index + 2; // La fila correspondiente

        // Aplicar color dorado a la fila si el Régimen Tributario es 'Retención'
        if ($factura['Régimen Tributario'] === 'Retención') {
            $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'FFD700'], // Dorado
                ],
            ]);
        }
    }

    return [];
}

}
