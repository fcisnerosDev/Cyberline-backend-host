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
        'flgEstado'

    ];

    public $timestamps = false;
}
