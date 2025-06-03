<?php

namespace App\Helpers;

use App\Models\CorreoReporta;

class CorreoReportaHelper
{
    public static function getCorreosReportaById($idTicket)
    {
        // Obtener todos los correos activos y relevantes para el idTicket
        $correos = CorreoReporta::where('idOrigen', $idTicket)
            // Asegúrate de que solo selecciona correos activos
            ->get();

        // Formatear los correos en un array para distinguir los tipos de envío
        $result = [
            'para' => [],
            'cc' => []
        ];

        foreach ($correos as $correo) {
            if ($correo->tipoReporta === 'T') {
                $result['para'][] = $correo->correo;
            } elseif ($correo->tipoReporta === 'CC') {
                $result['cc'][] = $correo->correo;
            }
        }

        return $result;
    }



    public static function getCorreosReportaByAtencionId($idAtencion)
    {
        // Obtener todos los correos activos y relevantes para el idTicket
        $correos = CorreoReporta::where('idOrigen', $idAtencion)
            // Asegúrate de que solo selecciona correos activos
            ->get();

        // Formatear los correos en un array para distinguir los tipos de envío
        $result = [
            'para' => [],
            'cc' => []
        ];

        foreach ($correos as $correo) {
            if ($correo->tipoReporta === 'T') {
                $result['para'][] = $correo->correo;
            } elseif ($correo->tipoReporta === 'CC') {
                $result['cc'][] = $correo->correo;
            }
        }

        return $result;
    }
}
