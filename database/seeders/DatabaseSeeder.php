<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal do SIGEA — ordem respeita as dependências entre tabelas.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PerfilSeeder::class,
            SetorSeeder::class,
            CategoriaEventoSeeder::class,
            ConfiguracaoSeeder::class,
            UsuarioSeeder::class,
            EventoSimuladoSeeder::class,
        ]);
    }
}
