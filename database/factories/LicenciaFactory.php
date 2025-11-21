<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Licencia;
use App\Models\TipoLicencia;
use App\Models\User;

class LicenciaFactory extends Factory
{
    protected $model = Licencia::class;

    public function definition()
    {
        return [
            'tipo_licencia_id' => TipoLicencia::inRandomOrder()->first()?->id ?? TipoLicencia::factory()->create()->id,
            'user_id' => null,
            'clave' => strtoupper($this->faker->bothify('LIC-########')),
            'fecha_compra' => ($dt2 = $this->faker->optional()->dateTimeBetween('-2 years', 'now')) ? $dt2->format('Y-m-d') : null,
            'fecha_vencimiento' => ($dt = $this->faker->optional()->dateTimeBetween('now', '+2 years')) ? $dt->format('Y-m-d') : null,
            'stock' => $this->faker->numberBetween(0, 50),
        ];
    }
}
