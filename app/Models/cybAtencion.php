<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cybAtencion extends Model
{
    use HasFactory;
    protected $table = 'cybAtencion';
    protected $fillable = [
        'idTicket',
        'flgEstado',
        'flgMostrar'

    ];

    public $timestamps = false;

    public function scopeVisibleParaUsuario($query, $user)
    {
        if (!$user) {
            return $query;
        }

        if ($user->idPersonaPerspectiva === 'CSF') {
            return $query->where('flgMostrar', '1');
        }

        return $query;
    }
}
