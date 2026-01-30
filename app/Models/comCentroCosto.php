<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class comCentroCosto extends Model
{
     protected $table = 'comCentroCosto';
     protected $fillable = [
        'idCentroCosto',

    ];

    use HasFactory;
}
