<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReporteMonitoreoDesconocidoExcelMail;

class MailTestController extends Controller
{
    public function sendTestEmail()
    {
        // Mail::to('fcisneros@cyberline.com.pe')->send(new ReporteMonitoreoDesconocidoExcelMail());
        // return "Correo enviado correctamente con el archivo adjunto.";
    }
}
