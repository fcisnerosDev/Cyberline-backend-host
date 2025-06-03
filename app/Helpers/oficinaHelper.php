<?php

namespace App\Helpers;

use App\Models\Oficina;

class oficinaHelper
{
    public static function getoficinaById($idOficina)
    {
        return Oficina::where('idOficina', $idOficina)->first();
    }


}
