<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Puesto;

class PuestoSeeder extends Seeder
{
    public function run()
    {
        $puestos = ['Administrador', 'Técnico', 'Usuario', 'Encargado', 'Supervisor'];

        foreach ($puestos as $p) {
            Puesto::create(['nombre' => $p]);
        }
    }
}
