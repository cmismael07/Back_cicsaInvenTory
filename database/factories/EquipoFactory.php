<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Equipo;
use App\Models\TipoEquipo;
use App\Models\Ubicacion;
use App\Models\User;

class EquipoFactory extends Factory
{
    protected $model = Equipo::class;

    public function definition()
    {
        return [
            'tipo_equipo_id' => TipoEquipo::inRandomOrder()->first()?->id ?? TipoEquipo::factory()->create()->id,
            'ubicacion_id' => Ubicacion::inRandomOrder()->first()?->id ?? Ubicacion::factory()->create()->id,
            'responsable_id' => User::inRandomOrder()->first()?->id ?? User::factory()->create()->id,
            'codigo_activo' => strtoupper($this->faker->bothify('EQ-####')),
            'marca' => $this->faker->company(),
            'modelo' => $this->faker->word(),
            'serial' => strtoupper($this->faker->bothify('SN-#####')),
            'estado' => 'activo',
            'fecha_compra' => $this->faker->date(),
            'garantia_meses' => $this->faker->numberBetween(0, 36),
            'valor_compra' => $this->faker->randomFloat(2, 100, 3000),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
