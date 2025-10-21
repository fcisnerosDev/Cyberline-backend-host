<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\UserCyberV6;

class LegacyRolesPermissionsSeeder extends Seeder
{
    public function run()
    {
        $guard = 'sanctum'; // Guard para Spatie y usuario legacy

        // ----------------------
        // PERMISOS
        // ----------------------
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

        // Crear permisos con el guard
        foreach ($todosPermisos as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => $guard,
            ]);
        }

        // ----------------------
        // ROLES
        // ----------------------
        $roles = [
            'Analista de Monitoreo' => $permisosMonitoreo,
            'Contabilidad' => $permisosFinanzas,
            'Desarrollo' => [],
            'Administracion' => $todosPermisos,
        ];

        // Crear roles con guard y asignar permisos
        foreach ($roles as $rolNombre => $rolPermisos) {
            $rol = Role::firstOrCreate([
                'name' => $rolNombre,
                'guard_name' => $guard,
            ]);

            if (!empty($rolPermisos)) {
                $rol->syncPermissions(
                    Permission::whereIn('name', $rolPermisos)
                              ->where('guard_name', $guard)
                              ->get()
                );
            }
        }

        // ----------------------
        // ASIGNAR ROL AL USUARIO LEGACY
        // ----------------------
        $usuarioPrueba = UserCyberV6::where('idPersona', 3177)->first();

        if ($usuarioPrueba) {
            $usuarioPrueba->assignRole('Administracion'); // Usa automáticamente el guard del modelo
            echo "Rol 'Contabilidad' asignado al usuario {$usuarioPrueba->usuario}\n";
        } else {
            echo "Usuario con idPersona 3177 no encontrado.\n";
        }

        echo "Seeder ejecutado correctamente.\n";
    }
}
