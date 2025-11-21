<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Ubicacion;

class UbicacionFactory extends Factory
{
    protected $model = Ubicacion::class;

    public function definition()
    {
        return [
            'nombre' => $this->faker->city(),
            'detalle' => $this->faker->address(),
        ];
    }
}
