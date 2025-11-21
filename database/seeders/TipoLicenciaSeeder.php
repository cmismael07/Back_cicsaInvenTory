<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoLicencia;

class TipoLicenciaSeeder extends Seeder
{
    public function run()
    {
        $tipos = [
            ['nombre' => 'Windows', 'version' => '10/11'],
            ['nombre' => 'Office', 'version' => '365'],
            ['nombre' => 'Antivirus', 'version' => '2025'],
        ];

        foreach ($tipos as $t) {
            TipoLicencia::create($t);
        }
    }
}
