<?php

namespace App\Models\Helpdesk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpdeskAttachment extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk_cyberline';

    protected $fillable = [
        'message_id',
        'filename',
        'mime_type',
        'path',
        'content_id'
    ];

    /**
     * Relación con el mensaje al que pertenece
     */
    public function message()
    {
        return $this->belongsTo(HelpdeskMessage::class, 'message_id');
    }
}
