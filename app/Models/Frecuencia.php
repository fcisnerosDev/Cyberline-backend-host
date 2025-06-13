<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Frecuencia extends Model
{
    protected $table = 'maeFrecuencia';
    protected $primaryKey = 'idFrecuencia';
    public $timestamps = false;
    protected $fillable = [
        'idFrecuencia',

    ];
}
