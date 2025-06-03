<?php

namespace App\Helpers;

use App\Models\Compania;

class companiaFactHelper
{
    public static function getCompaniaById($cod_compania)
    {
        return Compania::where('idCompania', $cod_compania)
            ->first();
            
    }
}
