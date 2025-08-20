<?php

namespace App\Exports;

use App\Models\Facturacion_Nuevo\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

namespace App\Exports;

use App\Models\Facturacion_Nuevo\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class FacturasExport implements FromCollection, WithHeadings, WithStyles
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        // Normalizamos las fechas a solo día (YYYY-MM-DD)
        $this->startDate = $startDate ? Carbon::parse($startDate)->toDateString() : null;
        $this->endDate   = $endDate ? Carbon::parse($endDate)->toDateString() : null;
    }

    public function collection()
    {
        $query = Invoice::with(['client', 'sunatResponse', 'details', 'cuotas', 'detraccion'])
            ->where('serie', 'F001');

        // Filtros de fecha ignorando la hora
        if ($this->startDate && $this->endDate) {
            $query->whereDate('created_at', '>=', $this->startDate)
                  ->whereDate('created_at', '<=', $this->endDate);
        } elseif ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }
        
        $facturas = $query->get();
        return $facturas->flatMap(function ($factura) {
            $cliente = $factura->client;

            // Nacionalidad
            $nacionalidad = '';
            if ($cliente) {
                if ((string) $cliente->tipo_doc === '6') {
                    $nacionalidad = 'Nacional';
                } elseif ((string) $cliente->tipo_doc === 'C') {
                    $nacionalidad = 'Extranjero';
                }
            }

            // Tipo de operación
            $tipoOperacionMap = [
                '0101' => 'Venta interna',
                '1001' => 'Detracción',
                '0200' => 'Exportación',
            ];
            $tipoOperacion = $tipoOperacionMap[$factura->tipo_operacion] ?? $factura->tipo_operacion;

            // Método de pago
            $paymentMethodMap = [
                1 => 'Cheque',
                2 => 'Depósito BCP Soles',
                3 => 'Depósito BCP Dólares',
                4 => 'Efectivo',
                5 => 'Retención',
            ];
            $metodoPago = $paymentMethodMap[$factura->payment_method_id] ?? '';

            // Estado de pago
            $estadoPagoMap = [
                1 => 'Cancelado',
                2 => 'Pendiente',
                3 => 'Anulado',
            ];
            $estadoPago = $estadoPagoMap[$factura->estado_pago_id] ?? '';

            // Tipo de pago
            $tipoPagoMap = [
                1 => 'Parcial',
                2 => 'Total',
            ];
            $tipoPago = $tipoPagoMap[$factura->tipo_pago] ?? '';

            // Estado SUNAT
            $estadoSunat = 'Pendiente';
            if ($factura->sunatResponse) {
                switch ($factura->sunatResponse->success) {
                    case 1:
                        $estadoSunat = 'Aceptado';
                        break;
                    case 3:
                        $estadoSunat = 'Anulado';
                        break;
                }
            }

            // Obtener la primera cuota (puedes ajustar si quieres todas)
            $cuota = $factura->cuotas->first();
            $montoCuota = $cuota->monto ?? '';
            $fechaPagoCuota = $cuota->fecha_pago ?? '';

            // Obtener detracción
            $detraccion = $factura->detraccion;
            $percentDetraccion = $detraccion->percent ?? '';
            $tipoCambio = $detraccion->tipo_cambio ?? '';
            $valorDetraccion = $detraccion->valor_detraccion ?? '';

            // Recorremos los detalles y repetimos cabecera
            return $factura->details->map(function ($detalle) use (
                $factura,
                $cliente,
                $nacionalidad,
                $tipoOperacion,
                $metodoPago,
                $estadoPago,
                $tipoPago,
                $estadoSunat,
                $montoCuota,
                $fechaPagoCuota,
                $percentDetraccion,
                $tipoCambio,
                $valorDetraccion
            ) {
                return [
                    // Datos de factura
                    'id'                 => $factura->id,
                    'serie'              => $factura->serie,
                    'correlativo'        => $factura->correlativo,
                    'fecha_emision'      => $factura->fecha_emision,
                    'forma_pago_tipo'    => $factura->forma_pago_tipo,
                    'tipo_moneda'        => $factura->tipo_moneda,
                    'valor_venta'        => $factura->valor_venta,
                    'subtotal'           => $factura->subtotal,
                    'fecha_envio_sunat'  => $factura->created_at,

                    // Cliente
                    'rzn_social'         => $cliente->rzn_social ?? '',
                    'num_doc'            => $cliente->num_doc ?? '',
                    'nacionalidad'       => $nacionalidad,
                    'Referencia'         => $factura->Referencia,

                    // Mapeos
                    'tipo_operacion'     => $tipoOperacion,
                    'metodo_pago'        => $metodoPago,
                    'tipo_pago_pt'       => $tipoPago,

                    // Campos adicionales
                    'nota'               => $factura->nota ?? '',
                    'tasa'               => $factura->tasa ?? '',
                    'estado_pago'        => $estadoPago,
                    'monto_cancelado'    => $factura->monto_cancelado ?? '',
                    'fecha_cancelacion'  => $factura->fecha_cancelacion ?? '',

                    // Estado SUNAT
                    'estado_sunat'       => $estadoSunat,

                    // === CUOTAS ===
                    'monto_cuota'        => $montoCuota,
                    'fecha_pago_cuota'   => $fechaPagoCuota,

                    // === DETRACCION ===
                    'percent_detraccion' => $percentDetraccion,
                    'tipo_cambio'        => $tipoCambio,
                    'valor_detraccion'   => $valorDetraccion,

                    // === DETALLES ===
                    'cod_producto'       => $detalle->cod_producto,
                    'descripcion'        => $detalle->descripcion,
                    'cantidad'           => $detalle->cantidad,
                    'mto_valor_unitario' => $detalle->mto_valor_unitario,
                    'mto_base_igv'       => $detalle->mto_base_igv,
                    'porcentaje_igv'     => $detalle->porcentaje_igv,
                    'igv'                => $detalle->igv,
                    'total_impuestos'    => $detalle->total_impuestos,
                    'mto_precio_unitario' => $detalle->mto_precio_unitario,
                    'mto_valor_venta'    => $detalle->mto_valor_venta,
                    'description_service' => $detalle->description_service,
                ];
            });
        });
    }

    public function headings(): array
    {
        return [
            // Factura
            'ID',
            'Serie',
            'Correlativo',
            'Fecha de Emisión',
            'Tipo de pago',
            'Moneda',
            'Valor de Venta',
            'Subtotal',
            'Fecha de envío a Sunat',

            // Cliente
            'Razón Social',
            'Número Documento',
            'Nacionalidad',
            'Referencia',

            // Mapeos
            'Tipo de Operación',
            'Método de Pago',
            'Tipo de Pago (Parcial/Total)',

            // Adicionales
            'Nota',
            'Tasa',
            'Estado de Pago',
            'Monto Cancelado',
            'Fecha de Cancelación',

            // SUNAT
            'Estado SUNAT',

            // === CUOTAS ===
            'Monto Cuota',
            'Fecha Pago Cuota',

            // === DETRACCION ===
            'Porcentaje Detracción',
            'Tipo Cambio',
            'Valor Detracción',

            // === DETALLES ===
            'Código Producto',
            'Descripción',
            'Cantidad',
            'Valor Unitario',
            'Base IGV',
            'Porcentaje IGV',
            'IGV',
            'Total Impuestos',
            'Precio Unitario',
            'Valor Venta',
            'Descripción Servicio',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = 'AI'; // Ajustado a la última columna (35 columnas aprox)
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0000FF'],
            ],
        ]);

        foreach (range('A', $lastColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
