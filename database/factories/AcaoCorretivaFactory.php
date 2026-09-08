<?php

namespace Database\Factories;

use App\Enums\StatusAcao;
use App\Enums\TipoAcao;
use App\Models\EventoAdverso;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AcaoCorretiva>
 */
class AcaoCorretivaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'evento_adverso_id' => EventoAdverso::factory(),
            'titulo' => ucfirst(fake()->words(4, true)),
            'descricao' => fake()->paragraph(),
            'tipo' => fake()->randomElement(TipoAcao::cases()),
            'responsavel_id' => User::factory(),
            'prazo' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'status' => StatusAcao::Pendente,
        ];
    }

    public function concluida(): static
    {
        return $this->state(fn () => [
            'status' => StatusAcao::Concluida,
            'data_conclusao' => today()->toDateString(),
            'evidencia_conclusao' => fake()->sentence(),
        ]);
    }

    public function vencida(): static
    {
        return $this->state(fn () => [
            'prazo' => fake()->dateTimeBetween('-2 months', '-1 day')->format('Y-m-d'),
        ]);
    }
}
