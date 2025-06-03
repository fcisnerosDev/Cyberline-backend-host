<?php

namespace App\Http\Controllers\Api\Facturacion\NotasCredito;

use App\Models\mov_fact_x_cobr_cab;
use App\Traits\ServiceFacturacionTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Facturacion_Nuevo\NotasCredito;
use App\Models\Facturacion_Nuevo\Clients;
use App\Models\Facturacion_Nuevo\NotasDebito;
use Luecano\NumeroALetras\NumeroALetras;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
class NotasDebitoController extends Controller

{
    use ServiceFacturacionTrait;




    //     public function searchFacturaNd(Request $request)
    // {
    //     $request->validate([
    //         'nro_factura' => 'required|string|max:255',
    //     ]);

    //     $facturas = mov_fact_x_cobr_cab::where('nro_factura', 'LIKE', "%{$request->nro_factura}%")
    //         ->with(['compania', 'oficina', 'condicionPago'])
    //         ->get()
    //         ->map(function ($factura) {
    //             // Convertir cod_compania a número para eliminar ceros iniciales
    //             $factura->cod_compania = (int) $factura->cod_compania;

    //             // Agregar tipo de moneda
    //             $factura->tipoMoneda = $factura->cod_moneda == 2 ? 'USD' : ($factura->cod_moneda == 1 ? 'PEN' : 'DESCONOCIDO');

    //             // Obtener el último correlativo para la serie FD01
    //             $ultimanotaDebito = NotasCredito::where('serie', 'FD01')
    //                 ->orderBy('correlativo', 'desc')
    //                 ->first();

    //             $ultimoCorrelativo = $ultimanotaDebito ? (int) trim($ultimanotaDebito->correlativo) : 0;

    //             // Generar el próximo correlativo con 6 dígitos
    //             $proximoCorrelativo = str_pad($ultimoCorrelativo + 1, 6, '0', STR_PAD_LEFT);

    //             // Asignar la serie y correlativo correcto
    //             $factura->proximo_correlativo_nc = 'FD01-' . $proximoCorrelativo;

    //             // Obtener la descripción de la condición de pago
    //             $factura->dsc_cond_pag = $factura->condicionPago ? $factura->condicionPago->dsc_cond_pag : 'DESCONOCIDO';

    //             return $factura;
    //         });

    //     return response()->json($facturas);
    // }






    public function indexPagination(Request $request)
    {
        $query = NotasDebito::with('client', 'sunatResponse')
            ->where('serie', 'FD01');


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
                // unset($nota->sunatResponse); // Eliminar el objeto original de sunatResponse
            } else {
                $nota->Estado_Sunat = "No enviado a Sunat";
            }

            return $nota;
        }));

        return response()->json($response);
    }

    public function enviarNotaDebito(Request $request)
    {
        // Datos que se enviarán
        $data = $request->all();
        $endpoint = 'api/notes/send';

        $respuesta = $this->consumirServicioFacturacion($endpoint, $data, 'POST');

        return response()->json($respuesta);
    }

    public function generateMailNotasDebito($correlativo)
    {
        $notaDebito = NotasDebito::where('correlativo', $correlativo)->first();

        if (!$notaDebito) {
            return response()->json(['error' => 'Nota de débito no encontrada'], 404);
        }

        return view('Emails.Facturacion.EnvioDetalle', compact('notaDebito'));
    }


    public function generateMailInvoiceNotasDebito($correlativo)
    {
        $notaDebito = NotasDebito::where('correlativo', $correlativo)->first();

        if (!$notaDebito) {
            return response()->json(['error' => 'Nota de crédito no encontrada'], 404);
        }

        // Obtener el hash de la respuesta SUNAT
        $hash = $notaDebito->sunatResponse->hash ?? null;

        if (!$hash) {
            return response()->json(['error' => 'No se encontró el hash de SUNAT'], 404);
        }

        // Generar el código QR
        $qrCode = QrCode::size(90)->generate($hash);


        // Usar NumeroALetras para generar el texto en letras
        $formatter = new NumeroALetras();
        $monedaNombre = $notaDebito->tipo_moneda === 'PEN' ? 'NUEVOS SOLES' : 'DÓLARES AMERICANOS';
        $totalEnLetras = $formatter->toMoney($notaDebito->total, 2, $monedaNombre);

        // Recorrer los detalles de la factura y obtener precio en letras
        foreach ($notaDebito->invoiceDetails as $detalle) {
            $detalle->precio_unitario_letras = strtoupper($formatter->toMoney($detalle->mto_precio_unitario, 2, $monedaNombre));
        }

        // Pasar todo a la vista
        return view('Emails.Facturacion.invoiceNb', compact('notaDebito', 'totalEnLetras', 'qrCode'));
    }
}
