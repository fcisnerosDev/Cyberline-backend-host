<?php

namespace App\Models\Facturacion_Nuevo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Facturacion_Nuevo\Clients;
use App\Models\Facturacion_Nuevo\InvoiceDetail;
use App\Models\Facturacion_Nuevo\SunatResponse;

class FacturasProgramadas extends Model
{
    protected $connection = 'facturacion_cyberline';
    protected $table = 'invoice_recurrings';

    protected $fillable = [
        'supplier_id',
        'client_id',
        'descripcion',
        'frecuencia',
        'intervalo',
        'fecha_inicio',
        'fecha_proxima',
        'fecha_fin',
        'activo',
        'template_json'
    ];

    protected $casts = [
        'template_json' => 'array',
        'fecha_inicio' => 'date',
        'fecha_proxima' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean'
    ];
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    // public function invoiceDetails()
    // {
    //     return $this->hasMany(InvoiceDetail::class, 'invoice_id', 'id');
    // }
    // public function sunatResponse()
    // {
    //     return $this->hasOne(SunatResponse::class, 'invoice_id', 'id');
    // }
}
