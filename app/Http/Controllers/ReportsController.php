<?php

namespace App\Http\Controllers;

use App\Exports\FacturaExport;
use App\Helpers\TipoServicioHelper;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\CompaniaFactHelper;
use App\Helpers\CondicionPagoHelper;
use App\Helpers\FacturaDetalleHelper;
use App\Helpers\ServicioTipoHelper;
use App\Helpers\MonedaHelper;
use App\Models\mov_fact_x_cobr_cab;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    // public function export(Request $request)
    // {
    //     $nro_factura = $request->query('nro_factura');
    //     $fecha_inicio = $request->query('fecha_inicio');
    //     $fecha_fin = $request->query('fecha_fin');

    //     // Construcción de la consulta
    //     $query = mov_fact_x_cobr_cab::orderBy('fch_factura', 'desc');

    //     if (!empty($nro_factura)) {
    //         $query->where('nro_factura', $nro_factura);
    //     }

    //     if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    //         $fecha_inicio = Carbon::createFromFormat('Y-m-d', $fecha_inicio)->toDateString();
    //         $fecha_fin = Carbon::createFromFormat('Y-m-d', $fecha_fin)->toDateString();
    //         $query->whereBetween('fch_factura', [$fecha_inicio, $fecha_fin]);
    //     }

    //     $facturas = $query->get()->map(function ($factura) {
    //         // Obtener datos de la compañía
    //         $compania = CompaniaFactHelper::getCompaniaById($factura->cod_compania);
    //         $factura->razonSocial = $compania ? $compania->razonSocial : null;

    //         // Obtener descripción de la moneda
    //         $moneda = MonedaHelper::getMonedaById($factura->cod_moneda);
    //         $factura->dsc_moneda = $moneda ? $moneda->dsc_moneda : null;

    //         // Determinar régimen tributario
    //         $factura->regimenTributario = $factura->flgdetraccion == 1
    //             ? 'Detracción'
    //             : ($factura->flgRetencion == 1 ? 'Retención' : 'Rechazado por SUNAT');

    //         // Obtener detalles de la factura
    //         $detalles = FacturaDetalleHelper::getDetallesByIdFactura($factura->idFactura);

    //         foreach ($detalles as $detalle) {
    //             $servicio = ServicioTipoHelper::getTipoServicioById($detalle->cod_servicio);
    //             $detalle->dsc_servicio = $servicio ? $servicio->dsc_servicio : null;
    //         }

    //         $factura->detalles = $detalles;

    //         // Renombrar columnas para exportar con claridad
    //         return [
    //             'ID Factura' => $factura->idFactura,
    //             'Nro Factura' => $factura->nro_factura,
    //             'Fecha Factura' => $factura->fch_factura,
    //             'Razón Social' => $factura->razonSocial,
    //             'Moneda' => $factura->dsc_moneda,
    //             'Régimen Tributario' => $factura->regimenTributario,
    //             'Subtotal' => $factura->num_subtotal,
    //             'IGV' => $factura->num_igv,
    //             'Total' => $factura->num_total,
    //             'Detalles' => collect($factura->detalles)->map(function ($detalle) {
    //                 return [
    //                     'Descripción Servicio' => $detalle->dsc_servicio,
    //                     'Monto' => $detalle->dsc_monto,
    //                     'Cantidad' => $detalle->dsccant,
    //                     'Precio Unitario' => $detalle->dscpreuni,
    //                 ];
    //             })->toArray()
    //         ];
    //     });

    //     // Exportar a Excel
    //     return Excel::download(new FacturaExport($facturas), 'facturas.xlsx');
    // }
    public function export(Request $request)
    {
        $nro_factura = $request->query('nro_factura');
        $fecha_inicio = $request->query('fecha_inicio');
        $fecha_fin = $request->query('fecha_fin');

        // Construcción de la consulta
        $query = mov_fact_x_cobr_cab::orderBy('fch_factura', 'desc');

        if (!empty($nro_factura)) {
            $query->where('nro_factura', $nro_factura);
        }

        if (!empty($fecha_inicio) && !empty($fecha_fin)) {
            $fecha_inicio = Carbon::createFromFormat('Y-m-d', $fecha_inicio)->toDateString();
            $fecha_fin = Carbon::createFromFormat('Y-m-d', $fecha_fin)->toDateString();
            $query->whereBetween('fch_factura', [$fecha_inicio, $fecha_fin]);
        }

        $facturas = $query->get();

        $data = [];

        foreach ($facturas as $factura) {
            // Obtener datos de la compañía
            $compania = CompaniaFactHelper::getCompaniaById($factura->cod_compania);
            $Empresa = $compania ? $compania->nombreCorto : null;
            $razonSocial = $compania ? $compania->razonSocial : null;
            $ruc = $compania ? $compania->ruc : null;

            // Obtener descripción de la moneda
            $moneda = MonedaHelper::getMonedaById($factura->cod_moneda);
            $condicionpago = CondicionPagoHelper::getCondicionPagoById($factura->cod_cond_pag);
            $dsc_moneda = $moneda ? $moneda->dsc_moneda : null;
            $dsc_cond_pag = $condicionpago ? $condicionpago->dsc_cond_pag : null;

            // Determinar régimen tributario
            $regimenTributario = $factura->flgdetraccion == 1
                ? 'Detracción'
                : ($factura->flgRetencion == 1 ? 'Retención' : 'Rechazado por SUNAT');

            // Obtener detalles de la factura
            $detalles = FacturaDetalleHelper::getDetallesByIdFactura($factura->idFactura);

            if ($detalles->isEmpty()) {
                // Si la factura no tiene detalles, igual agregamos una fila con valores nulos para detalles
                $data[] = [
                    'ID Factura' => $factura->idFactura,
                    'Nro Factura' => $factura->nro_factura,
                    'Fecha Factura' => $factura->fch_factura,
                    'Compañia' => $Empresa,
                    'Razón Social' => $razonSocial,
                    'RUC' => $ruc,
                    'Moneda' => $dsc_moneda,
                    'Tipo de Cambio' => $factura->dsc_tip_cambio,
                    'Valor en soles' => $factura->Isoles,
                    'Valor en Dolares' => $factura->Idolares,
                    'Condición de Pago' => $dsc_cond_pag,
                    'Marca' => null,
                    'Referencia' => $factura->dsc_referencia,
                    'Régimen Tributario' => $regimenTributario,
                    'Subtotal' => $factura->num_subtotal,
                    'IGV' => $factura->num_igv,
                    'Total' => $factura->num_total,
                    'Descripción Servicio' => null,
                    'dsc_agregado' => null,
                    'Monto' => null,
                    'Cantidad' => null,
                    'Precio Unitario' => null,
                ];
            } else {
                foreach ($detalles as $detalle) {
                    // $servicio = ServicioTipoHelper::getTipoServicioById($detalle->cod_servicio);
                    // $dsc_servicio = $servicio ? $servicio->dsc_servicio : null;
                    $servicio = ServicioTipoHelper::getTipoServicioById($detalle->cod_servicio);
                    $dsc_servicio = $servicio ? $servicio->dsc_servicio : null;

                    // Verificar si existe el servicio antes de acceder a 'cod_tipo_serv'
                    $Tipo_servicio = null;
                    if ($servicio && $servicio->cod_tipo_serv) {
                        $tipoServicio = TipoServicioHelper::getTipoServicioById($servicio->cod_tipo_serv);
                        $Tipo_servicio = $tipoServicio ? $tipoServicio->dsc_tipo_serv : null;
                    }


                    $data[] = [
                        'ID Factura' => $factura->idFactura,
                        'N° de Factura' => $factura->nro_factura,
                        'Fecha Factura' => $factura->fch_factura,
                        'Compañia' => $Empresa,
                        'Razón Social' => $razonSocial,
                        'RUC' => $ruc,
                        'Moneda' => $dsc_moneda,
                        'Tipo de Cambio' => $factura->dsc_tip_cambio,
                        'Valor en soles' => $factura->Isoles,
                        'Valor en Dolares' => $factura->Idolares,
                        'Condición de Pago' => $dsc_cond_pag,
                        'Marca' => $detalle->dscmarca,
                        'Referencia' => $factura->dsc_referencia,
                        'Régimen Tributario' => $regimenTributario,
                        'Tipo de Servicio' => $Tipo_servicio,
                        'Descripción Servicio' => $dsc_servicio,
                        'Descripción Agregada' => $detalle->dsc_agregado,
                        'Monto' => $detalle->dsc_monto,
                        'Cantidad' => $detalle->dsccant,
                        'Precio Unitario' => $detalle->dscpreuni,
                        'Subtotal' => $factura->num_subtotal,
                        'IGV' => $factura->num_igv,
                        'Total' => $factura->num_total,
                    ];
                }
            }
        }

        // Exportar a Excel
        return Excel::download(new FacturaExport($data), 'facturas.xlsx');
    }
}
