<?php

namespace App\Helpers;

use App\Models\Trabajo;

class TrabajoHelper
{
    public static function getTrabajoAtencionById($idTrabajo)
    {
        return Trabajo::where('idTrabajo', $idTrabajo)->first();
    }


}
