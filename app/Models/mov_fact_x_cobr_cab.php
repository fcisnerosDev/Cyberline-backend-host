<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\factFacturaDetalle;
use Illuminate\Support\Facades\DB as FacadesDB;

class mov_fact_x_cobr_cab extends Model
{
    use HasFactory;
    protected $table = 'mov_fact_x_cobr_cab';


    public $timestamps = false;
    public function compania()
    {
        return DB::table('maeCompania')
        ->where('idCompania', $this->cod_compania)
        ->first();  // Retorna la primera fila que coincida
    }
    public function oficina()
    {
     
        return DB::table('comOficina')
        ->where('idCompania', $this->cod_compania)
        ->first();  //
    }


    public function detalles()
    {
        return $this->hasMany(FactFacturaDetalle::class, 'idFactura', 'idFactura');
    }
    public function condicionPago()
{
    return $this->belongsTo(mae_cond_pago::class, 'cod_cond_pag', 'cod_cond_pag');
}
}
