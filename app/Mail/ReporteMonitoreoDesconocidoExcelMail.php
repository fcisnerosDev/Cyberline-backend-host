<?php

namespace App\Mail;
#mime
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteEstadosMonitoreoLocal;

class ReporteMonitoreoDesconocidoExcelMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $nombreArchivo;
    protected $archivoExcel;

    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        $idNodoHijo = env('ID_NODO_HIJO', 'desconocido');
        $fechaActual = now()->format('d-m-Y');
        $horaActual = str_replace(' ', '', now()->format('H\h i'));

        $this->nombreArchivo = "reporte-monitoreos-desconocidos-{$idNodoHijo}-{$fechaActual}-{$horaActual}.xlsx";

        // Genera el archivo Excel y lo guarda en memoria
        $this->archivoExcel = Excel::raw(new ReporteEstadosMonitoreoLocal(), \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
            ->subject('Reporte Monitoreo Desconocido')
            ->view('Emails.reporte_monitoreo_desconocidos')
            ->attachData($this->archivoExcel, $this->nombreArchivo, [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
