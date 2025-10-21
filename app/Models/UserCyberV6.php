<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserCyberV6 extends Authenticatable implements JWTSubject
{
    use HasApiTokens, Notifiable, HasRoles;

    protected $table = 'maePersona';
    protected $primaryKey = 'idPersona';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'idPersonaNodo',
        'idPersonaPerspectiva',
        'usuario',
        'password',
        'nombre',
        'apellidos',
        'intentos_fallidos',
        'bloqueado',
    ];

    protected $hidden = ['password', 'remember_token'];

    // Indicar el guard por defecto para Spatie
    protected $guard_name = 'sanctum';

    // JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Laravel Auth (login por usuario en lugar de id)
    public function getAuthIdentifierName()
    {
        return 'usuario';
    }
}
