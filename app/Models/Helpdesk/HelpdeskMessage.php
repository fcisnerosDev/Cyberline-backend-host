<?php

namespace App\Models\Helpdesk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpdeskMessage extends Model
{
    use HasFactory;

    // Conexión específica
    protected $connection = 'helpdesk_cyberline';

    // Tabla
    protected $table = 'helpdesk_messages';

    // Campos rellenables
    protected $fillable = [
        'message_id',
        'subject',
        'from_name',
        'from_email',
        'body',
        'seen',
        'date',
    ];

    // Casts
    protected $casts = [
        'seen' => 'boolean',
        'date' => 'datetime',
    ];

    /**
     * Relación con los destinatarios
     */
    public function recipients()
    {
        return $this->hasMany(HelpdeskRecipient::class, 'message_id', 'id');
    }

    /**
     * Relación para los destinatarios "to"
     */
    public function toRecipients()
    {
        return $this->recipients()->where('type', 'to');
    }

    /**
     * Relación para los destinatarios "cc"
     */
    public function ccRecipients()
    {
        return $this->recipients()->where('type', 'cc');
    }

       /**
     * Relación con attachments
     */
    public function attachments()
    {
        return $this->hasMany(HelpdeskAttachment::class, 'message_id');
    }
}
