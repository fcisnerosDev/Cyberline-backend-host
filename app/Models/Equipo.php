<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    protected $table = 'comEquipo';
    protected $primaryKey = 'idEquipo';
    public $timestamps = false;
    protected $fillable = [
        'idEquipo',

    ];
}
