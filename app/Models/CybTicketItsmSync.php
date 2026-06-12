<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CybTicketItsmSync extends Model
{
    use HasFactory;

    protected $table = 'cybTicket_itsm_sync';

    protected $fillable = [
        'ticket_id',
        'ticket_id_itsm',
        'sync_itsm',
        'estado',
        'fecha_sync',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    public $timestamps = false;
}
