<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    protected $table = 'cybTicket';

    public function scopeCompaniaPerspectiva($query, $user)
{
    if (!$user) {
        return $query;
    }

    return match ($user->idPersonaPerspectiva) {
        'CSF' => $query->where('idCompaniaSolicitante', 870),
        default => $query
    };
}
}
