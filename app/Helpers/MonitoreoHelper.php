<?php

namespace App\Helpers;

use Carbon\Carbon;
use Carbon\CarbonInterval;

class MonitoreoHelper
{
    /**
     * Obtiene el color HEX según el tiempo transcurrido
     *
     * @param string|Carbon $fechaUltimaVerificacion
     * @return string
     */
    public static function colorTiempoTranscurrido($fechaUltimaVerificacion)
    {
        $ahora = now();
        $tiempo = $fechaUltimaVerificacion instanceof Carbon
            ? $ahora->diffInMinutes($fechaUltimaVerificacion)
            : $ahora->diffInMinutes(Carbon::parse($fechaUltimaVerificacion));

        //  Reglas de colores
        if ($tiempo < 5) {
            return '#85903B'; // < 5 min
        } elseif ($tiempo < 15) {
            return '#C09939'; // < 15 min
        } elseif ($tiempo < 60) {
            return '#C9721A'; // < 1 hora
        } elseif ($tiempo < 300) { // 5 horas = 300 min
            return '#F5511E'; // 1-5 horas
        } else {
            return '#CB1C1A'; // > 5 horas
        }
    }
}
