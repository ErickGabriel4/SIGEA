<?php

namespace Database\Factories;

use App\Enums\CategoriaProfissional;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('senha123'),
            'matricula' => fake()->unique()->numerify('MAT-#####'),
            'registro_conselho' => null,
            'categoria_profissional' => fake()->randomElement(CategoriaProfissional::cases()),
            'cargo' => fake()->randomElement(['Assistencial', 'Plantonista', 'Coordenador(a)', 'Supervisor(a)']),
            'telefone' => fake()->numerify('(19) 9####-####'),
            'ativo' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function inativo(): static
    {
        return $this->state(fn () => ['ativo' => false]);
    }
}
