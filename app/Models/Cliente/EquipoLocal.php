<?php

namespace App\Models\Cliente;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipoLocal extends Model
{
    protected $connection = 'mysql_hijo';

    use HasFactory;
    protected $table = 'comEquipo';
    protected $primaryKey = 'idEquipo';
    public $timestamps = false;
   
}
