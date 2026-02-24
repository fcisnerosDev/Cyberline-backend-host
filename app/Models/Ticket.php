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

    // public function scopeSoloSolicitantesCSF($query)
    // {
    //     return $query->whereExists(function ($sub) {
    //         $sub->selectRaw(1)
    //             ->from('maePersona')
    //             ->whereColumn('maePersona.idPersona', 'cybTicket.idUsuarioSolicitante')
    //             ->whereColumn('maePersona.idPersonaNodo', 'cybTicket.idUsuarioSolicitanteNodo')
    //             ->where('maePersona.idPersonaPerspectiva', 'CSF');
    //     });
    // }

    public function scopeSoloSolicitantesCSF($query)
    {
        return $query->whereExists(function ($sub) {
            $sub->selectRaw(1)
                ->from('maePersona')
                ->whereColumn('maePersona.idPersona', 'cybTicket.idUsuarioSolicitante')
                ->whereColumn('maePersona.idPersonaNodo', 'cybTicket.idUsuarioSolicitanteNodo')
                ->where('maePersona.idPersonaPerspectiva', 'CSF');
        });
    }
}
