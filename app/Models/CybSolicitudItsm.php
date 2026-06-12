<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CybSolicitudItsm extends Model
{
protected $table = 'cyb_solicitud_itsm';

protected $fillable = [
'cyb_solicitud_id',
'ticket_id_itsm',
];
}