<?php

namespace Database\Seeders;

use App\Models\IdentificationType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            IdentificationTypeSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            SettingSeeder::class,
        ]);

        $identificationType = IdentificationType::query()->where('code', 'CC')->firstOrFail();
        $superAdminRole = Role::query()->where('name', 'super-admin')->firstOrFail();

        $user = User::factory()
            ->superAdmin()
            ->create([
                'identification_type_id' => $identificationType->id,
            ]);

        $user->roles()->sync([$superAdminRole->id]);
    }
}
