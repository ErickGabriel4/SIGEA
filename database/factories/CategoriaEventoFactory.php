<?php

namespace Database\Factories;

use App\Models\CategoriaEvento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriaEvento>
 */
class CategoriaEventoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->unique()->lexify('???.##')),
            'nome' => 'Categoria '.fake()->unique()->words(2, true),
            'descricao' => fake()->sentence(),
            'categoria_pai_id' => null,
            'codigo_icps' => null,
            'notificavel_anvisa' => false,
            'exige_investigacao' => false,
            'ativo' => true,
        ];
    }

    public function filhaDe(int $paiId): static
    {
        return $this->state(fn () => ['categoria_pai_id' => $paiId]);
    }
}
