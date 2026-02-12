<?php

namespace App\Models\Helpdesk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpdeskRecipient extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk_cyberline';
    protected $table = 'helpdesk_recipients';

    protected $fillable = [
        'message_id',
        'type',
        'name',
        'email',
        'full',
    ];

    /**
     * Relación inversa al mensaje
     */
    public function message()
    {
        return $this->belongsTo(HelpdeskMessage::class, 'message_id', 'id');
    }
}
