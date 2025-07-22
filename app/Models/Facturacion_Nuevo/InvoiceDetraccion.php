<?php

namespace App\Models\Facturacion_Nuevo;

use App\Models\Facturacion_Nuevo\Invoice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDetraccion extends Model
{
    use HasFactory;
    protected $connection = 'facturacion_cyberline';
    public $timestamps = false;
    protected $table = 'invoice_detracciones';

    protected $fillable = [
        'invoice_id',
        'cod_bien_detraccion',
        'cod_medio_pago',
        'cta_banco',
        'percent',
        'tipo_cambio',
        'valor_detraccion',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
