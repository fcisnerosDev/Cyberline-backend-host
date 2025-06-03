<?php

namespace App\Helpers;

use App\Models\mae_cond_pago;

class CondicionPagoHelper
{
    public static function getCondicionPagoById($cod_cond_pag)
    {
        return mae_cond_pago::where('cod_cond_pag', $cod_cond_pag)
            ->first();

    }
}
