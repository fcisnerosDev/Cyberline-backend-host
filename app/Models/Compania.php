<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compania extends Model
{
    protected $table = 'maeCompania';
    protected $fillable = [
        'idCompaniaNodo',

    ];

    use HasFactory;

    public function oficinas()
    {
        return $this->hasMany(Oficina::class, 'idCompania');
    }

    public function oficinaPrincipal()
    {
        return $this->hasOne(Oficina::class, 'idCompania', 'idCompania')
            ->where('flgSedePrincipal', '1');
    }
}
