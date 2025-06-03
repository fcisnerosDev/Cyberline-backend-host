<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oficina extends Model
{
     protected $table = 'comOficina';
     protected $fillable = [
        'idOficina',

    ];

    use HasFactory;
}
