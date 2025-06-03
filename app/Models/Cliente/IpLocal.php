<?php

namespace App\Models\Cliente;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpLocal extends Model
{
    protected $connection = 'mysql_hijo';

    use HasFactory;
    protected $table = 'comIp';
    protected $primaryKey = 'idIp';
    public $timestamps = false;

}
