<?php

namespace App\Helpers;

use App\Models\mae_servicio;

class ServicioTipoHelper
{
    public static function getTipoServicioById($cod_servicio)
    {
        return mae_servicio::where('cod_servicio', $cod_servicio)
            ->first();

    }
}
