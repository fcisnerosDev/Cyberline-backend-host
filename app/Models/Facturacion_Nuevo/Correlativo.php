<?php

namespace App\Models\Facturacion_Nuevo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Correlativo extends Model
{
    protected $connection = 'facturacion_cyberline';
     protected $table = 'correlativos';
     protected $fillable = [
        'id',

    ];

    use HasFactory;
}
