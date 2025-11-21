<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoEquipo;

class TipoEquipoSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['nombre' => 'Laptop', 'descripcion' => 'Computadora portátil'],
            ['nombre' => 'Desktop', 'descripcion' => 'Computadora de escritorio'],
            ['nombre' => 'Monitor', 'descripcion' => 'Pantalla externa'],
            ['nombre' => 'Impresora', 'descripcion' => 'Impresora láser o inyección'],
        ];

        foreach ($items as $it) {
            TipoEquipo::firstOrCreate(['nombre' => $it['nombre']], $it);
        }
    }
}
