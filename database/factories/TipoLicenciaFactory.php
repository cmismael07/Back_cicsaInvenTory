<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\TipoLicencia;

class TipoLicenciaFactory extends Factory
{
    protected $model = TipoLicencia::class;

    public function definition()
    {
        return [
            'nombre' => $this->faker->word(),
            'version' => $this->faker->bothify('v#.#'),
        ];
    }
}
