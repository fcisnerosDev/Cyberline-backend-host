<?php

namespace App\Models\Facturacion_Nuevo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clients extends Model
{
    protected $connection = 'facturacion_cyberline';
     protected $table = 'clients';
     protected $fillable = [
        'id',

    ];

    use HasFactory;
}
