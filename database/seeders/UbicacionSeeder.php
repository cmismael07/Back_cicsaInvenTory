<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\Ubicacion;

class UbicacionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['nombre' => 'Bodega IT', 'descripcion' => 'Almacén central de IT'],
            ['nombre' => 'Piso 2 - Ventas', 'descripcion' => 'Oficinas de ventas en piso 2'],
            ['nombre' => 'Dirección General', 'descripcion' => 'Oficina de la dirección'],
            ['nombre' => 'Almacén Bajas', 'descripcion' => 'Equipos dados de baja'],
        ];

        foreach ($items as $it) {
            $u = Ubicacion::firstOrCreate(['nombre' => $it['nombre']]);
            if (Schema::hasColumn('ubicaciones', 'descripcion') && ! empty($it['descripcion'])) {
                $u->update(['descripcion' => $it['descripcion']]);
            }
        }
    }
}
