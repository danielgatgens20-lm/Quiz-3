<?php

namespace Database\Factories;

use App\Models\Estudiante;
use App\Models\Docente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estudiante>
 */
class EstudianteFactory extends Factory
{
  public function definition(): array
    {
        return [
            'carne' => fake()->unique()->bothify('C#####'),
            'nombre' => fake()->firstName(),
            'apellidos' => fake()->lastName().' '.fake()->lastName(),
            'consejero_id' => Docente::factory(),
        ];
    }
}
