<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CybAtencionItsmSync extends Model
{
    protected $table = 'cybAtencion_itsm_sync';

    protected $fillable = [
        'id_atencion',
        'id_ticket',
        'ticket_id_itsm',
        'estado',
        'fecha_sync',
        'fecha_actualizacion'
    ];

    public $timestamps = false;
}
