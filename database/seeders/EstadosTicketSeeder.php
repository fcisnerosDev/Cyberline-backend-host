<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadosTicketSeeder extends Seeder
{
    public function run()
    {
        DB::table('Cyber_estadosTicket')->insert([
            ['flgStatus' => 0, 'nombre' => 'Asignado', 'descripcion' => ''],
            ['flgStatus' => 3, 'nombre' => 'En progreso', 'descripcion' => ''],
            ['flgStatus' => 6, 'nombre' => 'Pendiente', 'descripcion' => ''],
            ['flgStatus' => 2, 'nombre' => 'Resuelto', 'descripcion'  => ''],
            ['flgStatus' => 1, 'nombre' => 'Cerrado', 'descripcion'  => '']
        ]);
    }
}

