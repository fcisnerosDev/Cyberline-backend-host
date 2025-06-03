<?php

namespace App\Http\Controllers\Api\Facturacion\NotasCredito;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\mov_fact_x_cobr_cab;
use App\Traits\ServiceFacturacionTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Facturacion_Nuevo\NotasCredito;
use App\Models\Facturacion_Nuevo\Clients;
use Luecano\NumeroALetras\NumeroALetras;
// use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\Facade as PDF;

class NotasCreditoController extends Controller

{
    use ServiceFacturacionTrait;

    public function searchFactura(Request $request)
    {
        $request->validate([
            'nro_factura' => 'required|string|max:255',
        ]);

        $facturas = mov_fact_x_cobr_cab::where('nro_factura', 'LIKE', "%{$request->nro_factura}%")
            ->with(['detalles', 'condicionPago']) // No se usa 'oficina' ni 'compania'
            ->get()
            ->map(function ($factura) {
                $factura->cod_compania = (int) $factura->cod_compania;
                $factura->tipo_DE_moneda = $factura->cod_moneda == 2 ? 'USD' : ($factura->cod_moneda == 1 ? 'PEN' : 'DESCONOCIDO');
                $factura->tipoMoneda = $factura->tipo_DE_moneda;

                $factura->num_venta_noexo_igv = number_format($factura->num_venta_noexo_igv, 2, '.', '');
                $factura->num_subtotal = number_format($factura->num_subtotal, 2, '.', '');
                $factura->num_igv = number_format($factura->num_igv, 2, '.', '');
                $factura->num_total = number_format($factura->num_total, 2, '.', '');

                if ($factura->detalles) {
                    $contador = 1;
                    $factura->detalles = $factura->detalles->map(function ($detalle) use (&$contador) {
                        $detalle->dsc_monto = number_format($detalle->dsc_monto, 2, '.', '');
                        $detalle->codProducto = 'P' . str_pad($contador, 3, '0', STR_PAD_LEFT);
                        $detalle->unidad = 'NIU';
                        $contador++;
                        return $detalle;
                    });
                }

                $ultimaNotaCredito = NotasCredito::where('serie', 'FC01')->orderBy('id', 'desc')->first();
                $numeroCorrelativo = (int) preg_replace('/[^0-9]/', '', $ultimaNotaCredito->correlativo ?? '000000');
                $factura->proximo_correlativo_nc = '00' . str_pad($numeroCorrelativo + 1, 6, '0', STR_PAD_LEFT);

                $factura->dsc_cond_pag = $factura->condicionPago?->dsc_cond_pag ?? 'DESCONOCIDO';

                // Cargar desde métodos personalizados
                $factura->compania = $factura->compania();
                $factura->oficina = $factura->oficina();

                return $factura;
            });

        return response()->json($facturas);
    }




    // public function searchFactura(Request $request)
    // {
    //     $request->validate([
    //         'nro_factura' => 'required|string|max:255',
    //     ]);

    //     $facturas = mov_fact_x_cobr_cab::where('nro_factura', 'LIKE', "%{$request->nro_factura}%")
    //         ->with(['compania', 'oficina', 'detalles', 'condicionPago']) // Agregar la relación con mae_cond_pago
    //         ->get()
    //         ->map(function ($factura) {
    //             // Eliminar ceros iniciales solo si 'cod_compania' comienza con un cero
    //             $factura->cod_compania = (int) $factura->cod_compania;

    //             // Agregar tipo de moneda
    //             $factura->tipo_DE_moneda = $factura->cod_moneda == 2 ? 'USD' : ($factura->cod_moneda == 1 ? 'PEN' : 'DESCONOCIDO');
    //             $factura->tipoMoneda = $factura->cod_moneda == 2 ? 'USD' : ($factura->cod_moneda == 1 ? 'PEN' : 'DESCONOCIDO');

    //             // Formatear valores numéricos a dos decimales
    //             $factura->num_venta_noexo_igv = number_format($factura->num_venta_noexo_igv, 2, '.', '');
    //             $factura->num_subtotal = number_format($factura->num_subtotal, 2, '.', '');
    //             $factura->num_igv = number_format($factura->num_igv, 2, '.', '');
    //             $factura->num_total = number_format($factura->num_total, 2, '.', '');

    //             if ($factura->detalles) {
    //                 $contador = 1; // Contador para generar los códigos de item
    //                 $factura->detalles = $factura->detalles->map(function ($detalle) use (&$contador) {
    //                     $detalle->dsc_monto = number_format($detalle->dsc_monto, 2, '.', '');
    //                     $detalle->codProducto = 'P' . str_pad($contador, 3, '0', STR_PAD_LEFT); // Genera P001, P002, etc.
    //                     $detalle->unidad = 'NIU'; // Agrega el valor fijo "NIU"
    //                     $contador++; // Incrementa el contador para el siguiente producto
    //                     return $detalle;
    //                 });
    //             }

    //             // Obtener la última nota de crédito con la serie FC01
    //             $ultimaNotaCredito = NotasCredito::where('serie', 'FC01')->orderBy('id', 'desc')->first();

    //             // Obtener el último correlativo, asegurando que sea un string de 6 dígitos
    //             $ultimoCorrelativo = $ultimaNotaCredito ? $ultimaNotaCredito->correlativo : '000000';

    //             // Extraer solo los dígitos numéricos del correlativo
    //             $numeroCorrelativo = (int) preg_replace('/[^0-9]/', '', $ultimoCorrelativo);

    //             // Incrementar y asegurar que tenga 6 dígitos con ceros a la izquierda
    //             $proximoCorrelativo = str_pad($numeroCorrelativo + 1, 6, '0', STR_PAD_LEFT);

    //             // Formar el nuevo correlativo con la serie FC01
    //             $factura->proximo_correlativo_nc = '00' . $proximoCorrelativo;

    //             // Obtener la descripción de la condición de pago
    //             $factura->dsc_cond_pag = $factura->condicionPago ? $factura->condicionPago->dsc_cond_pag : 'DESCONOCIDO';

    //             return $factura;
    //         });

    //     return response()->json($facturas);
    // }


    public function indexPagination(Request $request)
    {
        $query = NotasCredito::with('client', 'sunatResponse')
            ->where('serie', 'FC01'); // Filtrar solo por la serie FC01

        if ($request->filled('num_doc_afectado')) {
            $query->where('num_doc_afectado', 'like', "%{$request->num_doc_afectado}%");
        }

        $response = $query->orderBy('id', 'desc')->paginate(20);

        // Modificar la colección antes de enviarla
        $response->setCollection($response->getCollection()->transform(function ($nota) {
            $nota->doc_afectado = $nota->tipo_doc_afectado === "01" ? "Factura" : $nota->tipo_doc_afectado;

            // Agregar Estado_Sunat basado en sunat_response.success
            if ($nota->sunatResponse) {
                $nota->Estado_Sunat = $nota->sunatResponse->success == 1 ? "Aceptado en Sunat" : "Rechazado en Sunat";
            } else {
                $nota->Estado_Sunat = "No enviado a Sunat";
            }

            return $nota;
        }));

        return response()->json($response);
    }


    public function enviarNotaCredito(Request $request)
    {
        // Datos que se enviarán
        $data = $request->all();
        $endpoint = 'api/notes/send';

        $respuesta = $this->consumirServicioFacturacion($endpoint, $data, 'POST');

        return response()->json($respuesta);
    }

    public function generateMailNotasCredito($correlativo)
    {
        $notaCredito = NotasCredito::where('correlativo', $correlativo)->first();

        if (!$notaCredito) {
            return response()->json(['error' => 'Nota de crédito no encontrada'], 404);
        }

        return view('Emails.Facturacion.EnvioDetalle', compact('notaCredito'));
    }

    // public function generateMailInvoiceNotasCredito($correlativo)
    // {
    //     $notaCredito = NotasCredito::where('correlativo', $correlativo)->first();

    //     if (!$notaCredito) {
    //         return response()->json(['error' => 'Nota de crédito no encontrada'], 404);
    //     }

    //     $formatter = new NumeroALetras();
    //     $monedaNombre = $notaCredito->tipo_moneda === 'PEN' ? 'NUEVOS SOLES' : 'DÓLARES AMERICANOS';

    //     // Generar texto en letras para el total
    //     $totalEnLetras = $formatter->toMoney($notaCredito->total, 2, $monedaNombre);

    //     // Recorrer los detalles de la factura y obtener precio en letras
    //     foreach ($notaCredito->invoiceDetails as $detalle) {
    //         $detalle->precio_unitario_letras = strtoupper($formatter->toMoney($detalle->mto_precio_unitario, 2, $monedaNombre));
    //     }

    //     return view('Emails.Facturacion.invoice', compact('notaCredito', 'totalEnLetras', 'monedaNombre'));
    // }

    public function generateMailInvoiceNotasCredito($correlativo)
    {
        $notaCredito = NotasCredito::where('correlativo', $correlativo)->first();

        if (!$notaCredito) {
            return response()->json(['error' => 'Nota de crédito no encontrada'], 404);
        }

        // Obtener el hash de la respuesta SUNAT
        $hash = $notaCredito->sunatResponse->hash ?? null;

        if (!$hash) {
            return response()->json(['error' => 'No se encontró el hash de SUNAT'], 404);
        }

        // Generar el código QR
        $qrCode = QrCode::size(90)->generate($hash);


        // Usar NumeroALetras para generar el texto en letras
        $formatter = new NumeroALetras();
        $monedaNombre = $notaCredito->tipo_moneda === 'PEN' ? 'NUEVOS SOLES' : 'DÓLARES AMERICANOS';
        $totalEnLetras = $formatter->toMoney($notaCredito->total, 2, $monedaNombre);

        // Recorrer los detalles de la factura y obtener precio en letras
        foreach ($notaCredito->invoiceDetails as $detalle) {
            $detalle->precio_unitario_letras = strtoupper($formatter->toMoney($detalle->mto_precio_unitario, 2, $monedaNombre));
        }

        // Pasar todo a la vista
        return view('Emails.Facturacion.invoice', compact('notaCredito', 'totalEnLetras', 'qrCode'));
    }



}
