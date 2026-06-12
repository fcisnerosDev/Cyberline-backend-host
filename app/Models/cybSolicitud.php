<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CybSolicitud extends Model
{
    protected $table = 'cybSolicitud';
    protected $primaryKey = 'idSolicitud';

    public $timestamps = false;

    protected $fillable = [
        'idSolicitudNodo',
        'idSolicitudPerspectiva',
        'idTipoSolicitud',
        'idTipoSolicitudNodo',
        'idArea',
        'idAreaNodo',
        'idUsuario',
        'idUsuarioNodo',
        'idTicket',
        'idTicketNodo',
        'flgStatusTicket',
        'idUsuarioModifico',
        'idUsuarioModificoNodo',
        'dscCtaOrigen',
        'ctaOrigen',
        'asunto',
        'to',
        'cc',
        'fechaMail',
        'fechaSolicitud',
        'flgStatusSolicitud',
        'fechaModificacion',
        'flgEstado',
        'fechaCreacion',
        'fechaRegistro',
        'flgSync',
        'flgSyncHijo',
        'flgSyncPadre',
        'fechaSyncHijo',
        'fechaSyncPadre',
    ];

}
