<?php

namespace App\Models\Facturacion_Nuevo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodosPago extends Model
{
    use HasFactory;
    protected $connection = 'facturacion_cyberline';
    protected $table = 'payment_methods';
}
