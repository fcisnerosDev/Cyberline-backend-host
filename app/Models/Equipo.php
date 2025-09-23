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
    public function oficina()
    {
        return $this->belongsTo(Oficina::class, 'idOficina', 'idOficina');
    }
    public function ips()
    {
        return $this->hasMany(Ip::class, 'idEquipo', 'idEquipo');
    }
}
