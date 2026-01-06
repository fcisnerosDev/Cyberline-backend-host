<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonMonitoreoTelegram extends Model
{
    protected $table = 'mon_monitoreo_telegram';

    protected $fillable = [
        'idMonitoreo',
        'idMonitoreoNodo',
        'idNodoPerspectiva',
        'last_notified_at',
    ];

    protected $casts = [
        'last_notified_at' => 'datetime',
    ];

    public function monitoreo()
    {
        return $this->belongsTo(Monitoreo::class, 'idMonitoreo', 'idMonitoreo');
    }
}
