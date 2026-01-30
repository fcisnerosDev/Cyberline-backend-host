<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Areas extends Model
{
    use HasFactory;
    protected $table = 'comArea';
    protected $primaryKey = 'idArea';
    public $timestamps = false;
    protected $fillable = [
        'idArea',

    ];
}
