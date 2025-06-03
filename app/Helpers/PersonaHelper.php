<?php

namespace App\Helpers;

use App\Models\Persona;

class PersonaHelper
{
    public static function getResponsableById($idPersona)
    {
        return Persona::where('idPersona', $idPersona)->first();

    }
    public static function getSolicitanteById($idPersona, $idUsuarioSolicitanteNodo)
    {
        return Persona::where('idPersona', $idPersona)
                      ->where('idPersonaNodo', $idUsuarioSolicitanteNodo)
                      ->first();
    }
}
