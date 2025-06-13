<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ip extends Model
{
    use HasFactory;
    protected $table = 'comIp';
    protected $primaryKey = 'idIp';
    public $timestamps = false;
    protected $fillable = [
        'idIp',

    ];
}
