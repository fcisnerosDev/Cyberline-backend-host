<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cybTrabajo extends Model
{
    use HasFactory;
    protected $table = 'cybTrabajo';
    protected $fillable = [
        'idTrabajo',
       

    ];

    public $timestamps = false;
}
