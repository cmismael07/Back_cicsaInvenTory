<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            \Database\Seeders\DepartamentoSeeder::class,
            \Database\Seeders\PuestoSeeder::class,
            \Database\Seeders\TipoEquipoSeeder::class,
            \Database\Seeders\TipoLicenciaSeeder::class,
            \Database\Seeders\UserSeeder::class,
        ]);

        // Seed equipos and licencias after types and users exist
        $this->call([
            \Database\Seeders\EquipoSeeder::class,
            \Database\Seeders\LicenciaSeeder::class,
        ]);
    }
}
