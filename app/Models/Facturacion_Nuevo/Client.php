<?php

namespace App\Models\Facturacion_Nuevo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $connection = 'facturacion_cyberline';

    use HasFactory;

    protected $fillable = [
        'tipo_doc',
        'num_doc',
        'rzn_social',
        'direccion',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
