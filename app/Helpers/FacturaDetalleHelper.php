<?php

namespace App\Helpers;

use App\Models\factFacturaDetalle;

class FacturaDetalleHelper
{
    public static function getDetallesByIdFactura($idFactura)
    {
          return factFacturaDetalle::where('idFactura', $idFactura)->get();
    }
}
