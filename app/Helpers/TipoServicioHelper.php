<?php

namespace App\Helpers;

use App\Models\mae_tipo_serv;

class TipoServicioHelper
{
    public static function getTipoServicioById($cod_tipo_serv)
    {
        return mae_tipo_serv::where('cod_tipo_serv', $cod_tipo_serv)->first();
    }
}
