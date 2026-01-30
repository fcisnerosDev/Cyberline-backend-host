<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreasPersona extends Model
{
    use HasFactory;
    protected $table = 'comAreaRelacionPersona';
    protected $primaryKey = 'idAreaPersona';
    public $timestamps = false;
    protected $fillable = [
        'idAreaPersona',

    ];
}
