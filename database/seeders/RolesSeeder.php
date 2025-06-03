<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener o crear permisos
        $verUsuariosPermission = Permission::firstOrCreate(['name' => 'ver usuarios']);
        $crearUsuariosPermission = Permission::firstOrCreate(['name' => 'crear usuarios']);
        $editarUsuariosPermission = Permission::firstOrCreate(['name' => 'editar usuarios']);
        $borrarUsuariosPermission = Permission::firstOrCreate(['name' => 'borrar usuarios']);

        // Crear roles
        $adminRole = Role::firstOrCreate(['name' => 'Administrador']);
        $clienteRole = Role::firstOrCreate(['name' => 'Cliente']);

        // Asignar permisos al rol de administrador
        $adminRole->givePermissionTo([$verUsuariosPermission,$editarUsuariosPermission, $crearUsuariosPermission, $borrarUsuariosPermission]);

        // Asignar permisos al rol de cliente
        $clienteRole->givePermissionTo($verUsuariosPermission);

        $this->command->info('Roles y permisos creados exitosamente.');
    }
}
