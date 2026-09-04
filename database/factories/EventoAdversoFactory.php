<?php

namespace Database\Factories;

use App\Enums\ClassificacaoIncidente;
use App\Enums\Evitabilidade;
use App\Enums\FaixaEtaria;
use App\Enums\GrauDano;
use App\Enums\SexoPaciente;
use App\Enums\StatusEvento;
use App\Enums\Turno;
use App\Models\CategoriaEvento;
use App\Models\Setor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\EventoAdverso>
 */
class EventoAdversoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'notificante_id' => User::factory(),
            'anonima' => false,
            'categoria_evento_id' => fn () => CategoriaEvento::whereNotNull('categoria_pai_id')->inRandomOrder()->value('id')
                ?? CategoriaEvento::factory()->filhaDe(CategoriaEvento::factory()->create()->id)->create()->id,
            'setor_ocorrencia_id' => fn () => Setor::inRandomOrder()->value('id') ?? Setor::factory()->create()->id,
            'data_ocorrencia' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'hora_ocorrencia' => fake()->time('H:i'),
            'turno' => fake()->randomElement(Turno::cases()),
            'local_especifico' => 'Leito ' . fake()->numberBetween(1, 40),
            'descricao' => fake()->paragraph(3),
            'fatores_contribuintes' => fake()->optional()->sentence(10),
            'acao_imediata' => fake()->optional()->sentence(8),
            'paciente_envolvido' => true,
            'paciente_codigo' => 'PAC-' . fake()->regexify('[a-f0-9]{16}'),
            'paciente_faixa_etaria' => fake()->randomElement(FaixaEtaria::cases()),
            'paciente_sexo' => fake()->randomElement(SexoPaciente::cases()),
            'classificacao' => ClassificacaoIncidente::EventoAdverso,
            'grau_dano' => GrauDano::Nenhum,
            'evitabilidade' => Evitabilidade::NaoAvaliado,
            'status' => StatusEvento::Notificado,
        ];
    }

    public function rascunho(): static
    {
        return $this->state(fn () => ['status' => StatusEvento::Rascunho]);
    }

    public function anonima(): static
    {
        return $this->state(fn () => ['anonima' => true, 'notificante_id' => null]);
    }

    public function classificado(): static
    {
        return $this->state(fn () => [
            'probabilidade' => fake()->numberBetween(1, 5),
            'severidade' => fake()->numberBetween(1, 5),
            'evitabilidade' => fake()->randomElement([
                Evitabilidade::Evitavel, Evitabilidade::ProvavelmenteEvitavel,
                Evitabilidade::ProvavelmenteInevitavel, Evitabilidade::Inevitavel,
            ]),
            'status' => StatusEvento::EmTriagem,
            'triado_em' => now(),
        ]);
    }
}
