<?php

namespace App\Http\Controllers\Api\Facturacion\Facturas;

use App\Exports\FacturasExport;
use App\Models\Facturacion_Nuevo\MetodosPago;
use App\Models\Facturacion_Nuevo\services;
use App\Models\Facturacion_Nuevo\ServiceType;
use Illuminate\Http\Request;
use App\Models\Compania;
use App\Models\Facturacion_Nuevo\Facturas;
use App\Models\Facturacion_Nuevo\Invoice;
use App\Models\Facturacion_Nuevo\InvoiceDetraccion;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\mov_fact_x_cobr_cab;
use App\Traits\ServiceFacturacionTrait;
use App\Http\Controllers\Controller;
use App\Models\Facturacion_Nuevo\NotasCredito;
use App\Models\Facturacion_Nuevo\EstadoPago;
use App\Models\Facturacion_Nuevo\Clients;
use Luecano\NumeroALetras\NumeroALetras;
// use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\Facade as PDF;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as FacadesExcel;

class FacturasElectronicasController extends Controller

{
    use ServiceFacturacionTrait;



    public function EstadoPagoAll()
    {
        $estados = EstadoPago::all(['id', 'nombre']);

        return response()->json([
            'success' => true,
            'data' => $estados,
        ]);
    }

    public function MetodosPagoAll()
    {
        $MetodosPago = MetodosPago::all(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $MetodosPago,
        ]);
    }
    public function ServiciosTipoAll()
    {
        $ServicesType = ServiceType::all(['id', 'name', 'code']);

        return response()->json([
            'success' => true,
            'data' => $ServicesType,
        ]);
    }

    public function ServiciosAll()
    {
        $Services = services::all(['id', 'name', 'service_type_id']);

        return response()->json([
            'success' => true,
            'data' => $Services,
        ]);
    }



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


    // public function indexPagination(Request $request)
    // {
    //     $query = Facturas::with('client', 'sunatResponse')
    //         ->where('serie', 'F001');

    //     if ($request->filled('num_doc_afectado')) {
    //         $query->where('num_doc_afectado', 'like', "%{$request->num_doc_afectado}%");
    //     }

    //     $response = $query->orderBy('id', 'desc')->paginate(20);

    //     $response->setCollection($response->getCollection()->transform(function ($factura) {
    //         $factura->doc_afectado = $factura->tipo_doc_afectado === "01" ? "Factura" : $factura->tipo_doc_afectado;

    //         $factura->Estado_Sunat = $factura->sunatResponse
    //             ? ($factura->sunatResponse->success == 1 ? "Aceptado en Sunat" : "Rechazado en Sunat")
    //             : "No enviado a Sunat";

    //         return $factura;
    //     }));

    //     return response()->json($response);
    // }
    public function indexPagination(Request $request)
    {
        $query = Facturas::with('client', 'sunatResponse')
            ->where('serie', 'F001'); // valor por defecto

        // Filtro por estado_pago_id
        if ($request->filled('estado_pago_id')) {
            $query->where('estado_pago_id', $request->estado_pago_id);
        }

        // Filtro por serie-correlativo (ej: F001-00004807)
        if ($request->filled('numeroFactura')) {
            $parts = explode('-', $request->numeroFactura);
            if (count($parts) === 2) {
                [$serie, $correlativo] = $parts;
                $query->where('serie', $serie)->where('correlativo', $correlativo);
            }
        }

        // Filtro por RUC del cliente
        if ($request->filled('ruc')) {
            $query->whereHas('client', function ($q) use ($request) {
                $q->where('num_doc', 'like', "%{$request->ruc}%");
            });
        }

        // Filtro por rango de fecha usando whereDate (sin DB::raw)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereDate('fecha_emision', '>=', $request->start_date)
                ->whereDate('fecha_emision', '<=', $request->end_date);
        } elseif ($request->filled('fecha')) {
            $query->whereDate('fecha_emision', $request->fecha);
        }

        $response = $query->orderBy('id', 'desc')->paginate(20);

        $response->setCollection($response->getCollection()->transform(function ($factura) {
            $factura->doc_afectado = $factura->tipo_doc_afectado === "01" ? "Factura" : $factura->tipo_doc_afectado;

            $factura->Estado_Sunat = $factura->sunatResponse
                ? ($factura->sunatResponse->success == 1
                    ? "Aceptado en Sunat"
                    : ($factura->sunatResponse->success == 3
                        ? "Factura Anulada"
                        : "Rechazado en Sunat"))
                : "No enviado a Sunat";

            return $factura;
        }));

        return response()->json($response);
    }




    public function getDetraccion($invoice_id)
    {
        $detraccion = InvoiceDetraccion::where('invoice_id', $invoice_id)->first();

        if (!$detraccion) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la detracción'
            ]);
        }

        return response()->json([
            'success' => true,
            'detraccion' => $detraccion
        ]);
    }

    public function GetCuotas($invoice_id)
    {
        $invoice = Invoice::with('cuotas')->find($invoice_id);

        if (!$invoice || $invoice->cuotas->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Cuotas no encontradas'], 404);
        }

        return response()->json([
            'success' => true,
            'cuotas' => $invoice->cuotas
        ]);
    }



    public function enviarFactura(Request $request)
    {
        // Datos que se enviarán
        $data = $request->all();
        $endpoint = 'api/invoices/send';

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


    // public function EnviarEstadoNC(Request $request)
    // {
    //     $data = $request->all();

    //     // Asegúrate de que el campo 'invoice' venga en el request
    //     if (!isset($data['invoice'])) {
    //         return response()->json(['error' => true, 'message' => 'Falta el ID de la factura (invoice)'], 422);
    //     }

    //     $invoiceId = $data['invoice'];
    //     $endpoint = "api/invoices/{$invoiceId}/estado";

    //     $respuesta = $this->consumirServicioFacturacion($endpoint, $data, 'PUT');

    //     return response()->json($respuesta);
    // }
    public function GetClientes(Request $request)
    {
        $clientes = Compania::with('oficinaPrincipal')
            ->where('flgEstado', '1')
            ->get();

        return response()->json([
            'success' => true,
            'clientes' => $clientes
        ]);
    }

    public function exportFacturas(Request $request)
    {
        $fechaHora = now()->format('d-m-Y H-i');
        $startDate = $request->query('start_date'); // o $request->input('start_date')
        $endDate   = $request->query('end_date');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new FacturasExport($startDate, $endDate),
            "reporte-facturas-{$fechaHora}.xlsx"
        );
    }
}
