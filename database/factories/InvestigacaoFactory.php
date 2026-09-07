<?php

namespace Database\Factories;

use App\Enums\MetodoInvestigacao;
use App\Enums\StatusInvestigacao;
use App\Models\EventoAdverso;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Investigacao>
 */
class InvestigacaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'evento_adverso_id' => EventoAdverso::factory(),
            'responsavel_id' => User::factory(),
            'metodo' => fake()->randomElement([MetodoInvestigacao::CincoPorques, MetodoInvestigacao::Ishikawa]),
            'data_inicio' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'data_prevista' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'descricao_analise' => fake()->paragraph(),
            'status' => StatusInvestigacao::EmAndamento,
        ];
    }

    public function concluida(): static
    {
        return $this->state(fn () => [
            'status' => StatusInvestigacao::Concluida,
            'causa_raiz' => fake()->sentence(),
            'conclusao' => fake()->paragraph(),
            'data_conclusao' => today()->toDateString(),
        ]);
    }
}
