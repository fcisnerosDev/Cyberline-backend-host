<?php

namespace App\Helpers;

use App\Models\mae_moneda;

class monedaHelper
{
    public static function getMonedaById($cod_moneda)
    {
        return mae_moneda::where('cod_moneda', $cod_moneda)
            ->first();

    }
}
