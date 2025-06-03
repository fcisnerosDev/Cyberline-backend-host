<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Obtener o crear permisos
        $verUsuariosPermission = Permission::firstOrCreate(['name' => 'ver usuarios']);
        $crearUsuariosPermission = Permission::firstOrCreate(['name' => 'crear usuarios']);
        $borrarUsuariosPermission = Permission::firstOrCreate(['name' => 'borrar usuarios']);

        // Crear roles
        $adminRole = Role::create(['name' => 'Administrador']);
        $clienteRole = Role::create(['name' => 'Cliente']);

        // Asignar permisos al rol de administrador
        $adminRole->givePermissionTo([$verUsuariosPermission, $crearUsuariosPermission, $borrarUsuariosPermission]);

        // Asignar permisos al rol de cliente
        $clienteRole->givePermissionTo($verUsuariosPermission);

        // Asignar el rol de administrador al usuario con ID 1
        $user = User::find(1);
        if ($user) {
            $user->assignRole('Administrador');
        } else {
            $this->command->info('El usuario con ID 1 no existe en la base de datos.');
        }
    }
}
