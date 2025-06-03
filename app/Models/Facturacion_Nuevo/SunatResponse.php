<?php

namespace App\Models\Facturacion_Nuevo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SunatResponse extends Model
{
    protected $connection = 'facturacion_cyberline';
     protected $table = 'sunat_responses';
     protected $fillable = [
        'id',

    ];

    use HasFactory;
}
