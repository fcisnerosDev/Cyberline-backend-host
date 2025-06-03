<?php

namespace App\Http\Controllers\Api;
use Carbon\Carbon;
use App\Helpers\companiaFactHelper;
use App\Helpers\FacturaDetalleHelper;
use App\Helpers\ServicioTipoHelper;
use App\Helpers\monedaHelper;
use App\Http\Controllers\Controller;
use App\Models\mov_fact_x_cobr_cab;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    // public function indexPagination(Request $request)
    // {
    //     $nro_factura = $request->query('nro_factura');
    //     $query = mov_fact_x_cobr_cab::orderBy('fch_factura', 'desc');

    //     if (!empty($nro_factura)) {
    //         $query->where('nro_factura', $nro_factura);
    //     }

    //     // Ejecutar paginación
    //     $response = $query->paginate(10);

    //     // Transformar la colección: agregar razonSocial, dsc_moneda, regimenTributario y detalles de factura
    //     $response->getCollection()->transform(function ($factura) {
    //         // Obtener razonSocial desde el helper
    //         $compania = CompaniaFactHelper::getCompaniaById($factura->cod_compania);
    //         $factura->razonSocial = $compania ? $compania->razonSocial : null;

    //         // Obtener dsc_moneda desde el helper
    //         $moneda = monedaHelper::getMonedaById($factura->cod_moneda);
    //         $factura->dsc_moneda = $moneda ? $moneda->dsc_moneda : null;

    //         // Determinar régimen tributario
    //         if ($factura->flgdetraccion == 1) {
    //             $factura->regimenTributario = 'Detracción';
    //         } elseif ($factura->flgRetencion == 1) {
    //             $factura->regimenTributario = 'Retención';
    //         } else {
    //             $factura->regimenTributario = 'Ninguno';
    //         }

    //         // Obtener los detalles de la factura y añadirlos
    //         $detalles = FacturaDetalleHelper::getDetallesByIdFactura($factura->idFactura);
    //         $factura->detalles = $detalles;

    //         return $factura;
    //     });

    //     return response()->json($response);
    // }

    public function indexPagination(Request $request)
    {
        $nro_factura = $request->query('nro_factura');
        $fecha_inicio = $request->query('fecha_inicio'); // Obtener la fecha de inicio desde el query
        $fecha_fin = $request->query('fecha_fin'); // Obtener la fecha de fin desde el query

        $query = mov_fact_x_cobr_cab::orderBy('fch_factura', 'desc');

        // Filtrar por número de factura si se proporciona
        if (!empty($nro_factura)) {
            $query->where('nro_factura', $nro_factura);
        }

        // Filtrar por rango de fechas si se proporcionan ambas fechas
        if (!empty($fecha_inicio) && !empty($fecha_fin)) {
            // Asegúrate de que las fechas estén en el formato correcto para la comparación
            // Convertir las fechas de cadena a instancias de Carbon en formato 'Y-m-d'
            $fecha_inicio = Carbon::createFromFormat('Y-m-d', $fecha_inicio)->toDateString();
            $fecha_fin = Carbon::createFromFormat('Y-m-d', $fecha_fin)->toDateString();

            // Filtrar las facturas dentro del rango de fechas
            $query->whereBetween('fch_factura', [$fecha_inicio, $fecha_fin]);
        }


        // Ejecutar paginación
        $response = $query->paginate(10);

        // Transformar la colección: agregar razonSocial, dsc_moneda, regimenTributario y detalles de factura
        $response->getCollection()->transform(function ($factura) {
            // Obtener razonSocial desde el helper
            $compania = CompaniaFactHelper::getCompaniaById($factura->cod_compania);
            $factura->razonSocial = $compania ? $compania->razonSocial : null;

            // Obtener dsc_moneda desde el helper
            $moneda = monedaHelper::getMonedaById($factura->cod_moneda);
            $factura->dsc_moneda = $moneda ? $moneda->dsc_moneda : null;

            // Determinar régimen tributario
            if ($factura->flgdetraccion == 1) {
                $factura->regimenTributario = 'Detracción';
            } elseif ($factura->flgRetencion == 1) {
                $factura->regimenTributario = 'Retención';
            } else {
                $factura->regimenTributario = 'Ninguno';
            }

            // Obtener los detalles de la factura
            $detalles = FacturaDetalleHelper::getDetallesByIdFactura($factura->idFactura);

            // Agregar información adicional a los detalles
            foreach ($detalles as $detalle) {
                // Obtener el tipo de servicio por cod_servicio
                $servicio = ServicioTipoHelper::getTipoServicioById($detalle->cod_servicio);
                $detalle->dsc_servicio = $servicio ? $servicio->dsc_servicio : null;
            }

            $factura->detalles = $detalles;

            return $factura;
        });

        return response()->json($response);
    }
}
