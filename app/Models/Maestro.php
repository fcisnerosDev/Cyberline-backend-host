<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maestro extends Model
{
    use HasFactory;
    protected $table = 'maeMaestro';
    protected $primaryKey = 'idMaestro';
    public $timestamps = false;
    protected $fillable = [
        'idMaestro',

    ];
}
