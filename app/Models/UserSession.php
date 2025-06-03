<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    use HasFactory;


    protected $table = 'user_sessions';

    // Atributos que pueden ser asignados masivamente
    protected $fillable = [
        'chat_id',
        'step',
        'attempts',
        'expires_at',
        'authenticated'
    ];


    protected $hidden = [];
    public $timestamps = true;
    // Relación de Usuario
    public function user()
    {
        return $this->belongsTo(UserCyberV6::class);
    }
}
