<?php

namespace Database\Factories;

use App\Enums\TipoSetor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Setor>
 */
class SetorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => 'Setor ' . fake()->unique()->word(),
            'sigla' => strtoupper(fake()->unique()->lexify('???')),
            'tipo' => fake()->randomElement(TipoSetor::cases()),
            'leitos' => fake()->numberBetween(5, 40),
            'ativo' => true,
        ];
    }
}
