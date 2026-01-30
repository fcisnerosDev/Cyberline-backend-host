<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Dato extends Model
{
    // No usa tabla directamente
    protected $table = null;

    // Evita timestamps
    public $timestamps = false;

    /**
     * Arma los parámetros del SP sp_getListaInfoDato
     * Respeta el orden y usa '' cuando no aplica
     */
    private static function parametros(array $data = []): array
    {
        return [
            $data['idPersona'] ?? '',
            $data['idPersonaNodo'] ?? '',
            $data['idArea'] ?? '',
            $data['idAreaNodo'] ?? '',
            $data['idOficina'] ?? '',
            $data['idOficinaNodo'] ?? '',
        ];
    }

    /**
     * Ejecuta el SP sp_getListaInfoDato
     */
    public static function getListaInfoDato(array $filtros = [])
    {
        return DB::select(
            'CALL sp_getListaInfoDato(?,?,?,?,?,?)',
            self::parametros($filtros)
        );
    }
}
