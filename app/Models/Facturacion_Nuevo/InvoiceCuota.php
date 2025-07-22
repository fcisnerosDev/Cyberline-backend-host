<?php

namespace App\Models\Facturacion_Nuevo;

use App\Models\Facturacion_Nuevo\Invoice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceCuota extends Model
{
    use HasFactory;
    protected $connection = 'facturacion_cyberline';
    protected $table = 'invoice_cuotas';

    protected $fillable = [
        'invoice_id',
        'monto',
        'fecha_pago',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
