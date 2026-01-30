<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoCorreo extends Model
{
     protected $table = 'maeGrupoCorreo';
     protected $fillable = [
        'idGrupoCorreo',

    ];

    use HasFactory;
}
