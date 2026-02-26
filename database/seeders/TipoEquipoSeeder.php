<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoEquipo;

class TipoEquipoSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['nombre' => 'Laptop', 'descripcion' => 'Computadora portátil', 'frecuencia_anual' => 1, 'considerar_recambio' => true],
            ['nombre' => 'Desktop', 'descripcion' => 'Computadora de escritorio', 'frecuencia_anual' => 1, 'considerar_recambio' => true],
            ['nombre' => 'Monitor', 'descripcion' => 'Pantalla externa', 'frecuencia_anual' => 1, 'considerar_recambio' => true],
            ['nombre' => 'Impresora', 'descripcion' => 'Impresora láser o inyección', 'frecuencia_anual' => 1, 'considerar_recambio' => false],
        ];

        foreach ($items as $it) {
            TipoEquipo::firstOrCreate(['nombre' => $it['nombre']], $it);
        }
    }
}
