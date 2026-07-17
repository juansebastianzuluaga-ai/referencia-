<?php

namespace Database\Seeders;

use App\Models\IdentificationType;
use Illuminate\Database\Seeder;

class IdentificationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            ['code' => 'CC', 'name' => 'Cedula de ciudadania'],
            ['code' => 'CE', 'name' => 'Cedula de extranjeria'],
            ['code' => 'TI', 'name' => 'Tarjeta de identidad'],
            ['code' => 'PAS', 'name' => 'Pasaporte'],
            ['code' => 'PEP', 'name' => 'Permiso especial de permanencia'],
        ])->each(fn (array $type) => IdentificationType::query()->updateOrCreate(
            ['code' => $type['code']],
            $type + ['is_active' => true],
        ));
    }
}
