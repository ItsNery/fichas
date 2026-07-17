<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'catalogos.ver', 'catalogos.crear', 'catalogos.editar', 'catalogos.eliminar', 'catalogos.importar',
            'datos.ver', 'datos.crear', 'datos.editar', 'datos.eliminar', 'datos.importar', 'datos.aprobar',
            'configuracion-fichas.ver', 'configuracion-fichas.crear', 'configuracion-fichas.editar', 'configuracion-fichas.eliminar',
            'municipios.ver', 'municipios.editar', 'municipios.instrumentos.ver', 'municipios.instrumentos.editar',
            'instrumentos.ver', 'instrumentos.crear', 'instrumentos.editar', 'instrumentos.eliminar', 'instrumentos.importar',
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar', 'usuarios.asignar-roles',
            'roles.ver', 'roles.crear', 'roles.editar', 'roles.eliminar', 'roles.asignar-permisos',
            'permisos.ver', 'permisos.crear', 'permisos.editar', 'permisos.eliminar',
            'auditoria.ver', 'dashboard.ejecutivo', 'salud-datos.ver',
            'diccionario.ver', 'diccionario.editar',
        ];

        foreach ($permissions as $name) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $this->description($name), 'is_system' => true]
            );
        }

        $roles = [
            'super_admin' => [
                'description' => 'Administración técnica completa del sistema.',
                'permissions' => $permissions,
                'is_system' => true,
            ],
            'gobernanza' => [
                'description' => 'Revisión, aprobación y gobierno de la información.',
                'permissions' => [
                    'dashboard.ejecutivo', 'salud-datos.ver', 'catalogos.ver', 'datos.ver', 'datos.aprobar',
                    'configuracion-fichas.ver', 'municipios.ver', 'instrumentos.ver',
                    'auditoria.ver', 'diccionario.ver', 'diccionario.editar',
                ],
                'is_system' => false,
            ],
            'analista' => [
                'description' => 'Consulta y análisis de información sin facultades de modificación.',
                'permissions' => [
                    'catalogos.ver', 'datos.ver', 'configuracion-fichas.ver',
                    'municipios.ver', 'instrumentos.ver', 'auditoria.ver',
                ],
                'is_system' => false,
            ],
            'capturista' => [
                'description' => 'Captura, edición propuesta e importación de información.',
                'permissions' => [
                    'catalogos.ver', 'catalogos.crear', 'catalogos.editar', 'catalogos.importar',
                    'datos.ver', 'datos.crear', 'datos.editar', 'datos.importar',
                    'municipios.ver', 'municipios.editar', 'municipios.instrumentos.ver', 'municipios.instrumentos.editar',
                    'instrumentos.ver', 'instrumentos.crear', 'instrumentos.editar', 'instrumentos.importar',
                ],
                'is_system' => false,
            ],
            'consultor' => [
                'description' => 'Acceso de solo lectura a información administrativa.',
                'permissions' => [
                    'catalogos.ver', 'datos.ver', 'configuracion-fichas.ver',
                    'municipios.ver', 'instrumentos.ver', 'auditoria.ver',
                ],
                'is_system' => false,
            ],
        ];

        foreach ($roles as $name => $configuration) {
            $role = Role::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $configuration['description'], 'is_system' => $configuration['is_system']]
            );
            $role->syncPermissions($configuration['permissions']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function description(string $permission): string
    {
        [$module, $action] = array_pad(explode('.', $permission, 2), 2, 'gestionar');
        return ucfirst(str_replace('-', ' ', $action)) . ' ' . str_replace('-', ' ', $module);
    }
}
