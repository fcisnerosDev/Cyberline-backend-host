<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OficinaItsm extends Model
{
    protected $table = 'oficina_itsm';

    protected $fillable = [
        'idOficina',
        'idCompania',
        'id_oficina_glpi',
        'nombre_glpi',
    ];

    public function oficina()
    {
        return $this->belongsTo(
            Oficina::class,
            ['idOficina', 'idCompania'],
            ['idOficina', 'idCompania']
        );
    }
}
