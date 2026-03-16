<?php

namespace App\Models\Heldesk_old;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    use HasFactory;
    protected $table = 'cybSolicitud';
    protected $primaryKey = 'idSolicitud';
    public $timestamps = false;
    protected $fillable = [
        'idSolicitud',

    ];
}
