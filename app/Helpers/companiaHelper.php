<?php

namespace App\Helpers;

use App\Models\Compania;

class companiaHelper
{
    public static function getCompaniaById($idTicketNodo, $idCompaniaSolicitante)
    {
        return Compania::where('idCompaniaNodo', $idTicketNodo)
            ->where('idCompania', $idCompaniaSolicitante)
            ->first();
    }

    public static function getCompaniaByIdTicket( $idCompaniaSolicitante)
    {
        return Compania::where('idCompania', $idCompaniaSolicitante)
            ->first();
    }
}
