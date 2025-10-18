<?php

namespace App\Helpers;

use App\Models\Maestro;

class EstadoHelper
{
    public static function getNombreEstado($idEstado)
    {
        if (!$idEstado) {
            return null;
        }

        $estado = Maestro::find($idEstado);

        return $estado ? $estado->nombre : null;
    }
}
