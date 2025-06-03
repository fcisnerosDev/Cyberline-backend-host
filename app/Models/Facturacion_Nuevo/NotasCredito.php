<?php

namespace App\Models\Facturacion_Nuevo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Facturacion_Nuevo\Clients;
use App\Models\Facturacion_Nuevo\InvoiceDetail;
use App\Models\Facturacion_Nuevo\SunatResponse;

class NotasCredito extends Model
{
    protected $connection = 'facturacion_cyberline';
    protected $table = 'invoices';
    protected $fillable = [
        'id',

    ];

    use HasFactory;

    public function client()
    {
        return $this->belongsTo(Clients::class, 'client_id', 'id');
    }

    public function invoiceDetails()
    {
        return $this->hasMany(InvoiceDetail::class, 'invoice_id', 'id');
    }
    public function sunatResponse()
    {
        return $this->hasOne(SunatResponse::class, 'invoice_id', 'id');
    }
}
