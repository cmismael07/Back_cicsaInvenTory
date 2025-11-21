<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Departamento;

class DepartamentoSeeder extends Seeder
{
    public function run()
    {
        $departamentos = ['Sistemas', 'Recursos Humanos', 'Finanzas', 'Operaciones', 'Compras'];

        foreach ($departamentos as $d) {
            Departamento::create(['nombre' => $d]);
        }
    }
}
