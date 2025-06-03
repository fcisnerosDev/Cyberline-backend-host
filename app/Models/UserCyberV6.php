<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserCyberV6 extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $table = 'maePersona'; // Nombre de la tabla
    protected $primaryKey = 'idPersona'; // Especifica la clave primaria
    public $incrementing = false; // Si no es auto-incremental
    protected $keyType = 'string'; // Tipo de la clave primaria (string o int)
 // Desactivar los timestamps
 public $timestamps = false;
    protected $fillable = [
        'nombre',
        'usuario',
        'password',
        'intentos_fallidos',
        'bloqueado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
