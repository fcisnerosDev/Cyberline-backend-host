<?php

namespace App\Models\Facturacion_Nuevo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $connection = 'facturacion_cyberline';

    use HasFactory;

    protected $fillable = [
        'ruc',
        'razon_social',
        'nombre_comercial',
        'ubigueo',
        'departamento',
        'provincia',
        'distrito',
        'urbanizacion',
        'direccion',
        'cod_local',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

}
