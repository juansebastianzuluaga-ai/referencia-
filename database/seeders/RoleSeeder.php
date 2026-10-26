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
                'description' => 'Administra usuarios y catalogos base',
                'permissions' => Permission::query()->pluck('name')->all(),
            ],
            'medico' => [
                'display_name' => 'Personal de referencia',
                'description' => 'Personal que gestiona las solicitudes de referencia',
                'permissions' => [
                    'solicitudes.view',
                    'clinicas.view',
                    'historico.view',
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
