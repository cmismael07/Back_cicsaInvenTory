<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Licencia;

class LicenciaSeeder extends Seeder
{
    public function run()
    {
        Licencia::factory()->count(20)->create();
    }
}
