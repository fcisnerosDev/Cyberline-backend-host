<?php

namespace App\Helpers;

use App\Models\cybAtencion;

class AtencionHelper
{
    public static function getAtencionesByTicketId($idTicket)
    {
        return cybAtencion::where('idTicket', $idTicket)->get();
    }


}
