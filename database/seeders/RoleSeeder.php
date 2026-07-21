<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'super-admin' => [
                'display_name' => 'Super administrador',
                'description' => 'Acceso total a la administracion del sistema',
                'permissions' => Permission::query()->pluck('name')->all(),
            ],
            'admin' => [
                'display_name' => 'Administrador',
                'description' => 'Administra usuarios, roles y catalogos base',
                'permissions' => [
                    'users.view',
                    'users.create',
                    'users.update',
                    'roles.view',
                    'permissions.view',
                    'identification-types.view',
                    'identification-types.create',
                    'identification-types.update',
                    'external-clinics.view',
                    'external-clinics.approve',
                    'external-clinics.reject',
                    'external-clinics.manage',
                ],
            ],
            'medico' => [
                'display_name' => 'Medico',
                'description' => 'Acceso base para profesionales medicos',
                'permissions' => [
                    'identification-types.view',
                ],
            ],
        ];

        foreach ($roles as $name => $data) {
            $role = Role::query()->updateOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'web',
                ],
                [
                    'display_name' => $data['display_name'],
                    'description' => $data['description'],
                    'is_active' => true,
                ],
            );

            $role->syncPermissions($data['permissions']);
        }
    }
}
