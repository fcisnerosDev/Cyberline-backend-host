<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\UserCyberV6;

class RolesPermissionsSeeder extends Seeder
{
    public function run()
    {
        $guard = 'sanctum'; // o 'web' según tu configuración

        $permisosMonitoreo = [
            'Ver Detalle de Monitoreo',
            'Dashboard de monitoreo',
            'Ver Equipos y oficinas',
        ];

        $permisosTickets = [
            'Ver Tickets',
            'Ver Atenciones',
        ];

        $permisosFinanzas = [
            'Ver Facturas',
            'Ver Notas de credito y Debito',
        ];

        $todosPermisos = array_merge($permisosMonitoreo, $permisosTickets, $permisosFinanzas);

        // Crear permisos con guard
        foreach ($todosPermisos as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => $guard,
            ]);
        }

        // Crear roles con guard y asignar permisos
        $roles = [
            'Analista de Monitoreo' => $permisosMonitoreo,
            'Contabilidad' => $permisosFinanzas,
            'Desarrollo' => [],
            'Administracion' => $todosPermisos,
        ];

        foreach ($roles as $rolNombre => $rolPermisos) {
            $rol = Role::firstOrCreate([
                'name' => $rolNombre,
                'guard_name' => $guard,
            ]);
            $rol->syncPermissions($rolPermisos);
        }

        // Asignar rol al usuario legacy de prueba
        $usuarioPrueba = UserCyberV6::where('idPersona', 1501)->first();
        if ($usuarioPrueba) {
            $usuarioPrueba->assignRole('Contabilidad');
            echo "Rol 'Contabilidad' asignado al usuario {$usuarioPrueba->usuario}\n";
        } else {
            echo "Usuario con idPersona 1501 no encontrado.\n";
        }
    }
}
