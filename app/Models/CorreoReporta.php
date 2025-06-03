<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorreoReporta extends Model
{
     protected $table = 'cybCorreoReporta';
     protected $fillable = [
        'idOrigen', //Id de Ticket o Id de atencion

    ];

    use HasFactory;
}
