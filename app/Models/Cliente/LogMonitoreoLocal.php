<?php

namespace App\Models\Cliente;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogMonitoreoLocal extends Model
{
    protected $connection = 'mysql_hijo';

    use HasFactory;
    protected $table = 'monLog';
    protected $primaryKey = 'idMonitoreo';
    public $timestamps = false;

}
