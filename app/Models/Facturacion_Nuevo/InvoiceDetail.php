<?php

namespace App\Models\Facturacion_Nuevo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    use HasFactory;
    protected $connection = 'facturacion_cyberline';

    protected $fillable = [
        'invoice_id',
        'cod_producto',
        'unidad',
        'cantidad',
        'mto_valor_unitario',
        'descripcion',
        'mto_base_igv',
        'porcentaje_igv',
        'igv',
        'tip_afe_igv',
        'total_impuestos',
        'mto_valor_venta',
        'mto_precio_unitario',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
