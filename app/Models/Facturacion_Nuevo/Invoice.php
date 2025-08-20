<?php

namespace App\Models\Facturacion_Nuevo;



use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $connection = 'facturacion_cyberline';

    use HasFactory;

    protected $fillable = [
        'ubl_version',
        'tipo_doc',
        'serie',
        'correlativo',
        'fecha_emision',
        'tipo_doc_afectado',
        'num_doc_afectado',
        'cod_motivo',
        'des_motivo',
        'forma_pago_tipo',
        'tipo_moneda',
        'valor_venta',
        'subtotal',
        'mto_imp_venta',
        'supplier_id',
        'client_id',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class);
    }
    public function cuotas()
    {
        return $this->hasMany(InvoiceCuota::class);
    }

    public function detraccion()
    {
        return $this->hasOne(InvoiceDetraccion::class);
    }
     public function sunatResponse()
    {
        return $this->hasOne(SunatResponse::class, 'invoice_id', 'id');
    }
}
