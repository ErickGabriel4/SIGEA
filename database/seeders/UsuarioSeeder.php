<?php

namespace Database\Seeders;

use App\Models\Setor;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuários fixos de demonstração (Seção 10). Os perfis de acesso (RBAC)
 * são atribuídos em iteração posterior.
 */
class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $setores = Setor::pluck('id');

        User::firstOrCreate(
            ['email' => 'admin@sigea.local'],
            [
                'nome' => 'Administrador de Demonstração',
                'password' => 'senha123',
                'categoria_profissional' => 'administrativo',
                'cargo' => 'Analista de Sistemas',
                'setor_id' => $setores->random(),
                'ativo' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'gestor@sigea.local'],
            [
                'nome' => 'Gestora de Segurança de Demonstração',
                'password' => 'senha123',
                'categoria_profissional' => 'enfermeiro',
                'registro_conselho' => 'COREN-SP 123456',
                'cargo' => 'Coordenadora do NSP',
                'setor_id' => $setores->random(),
                'ativo' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'notificador@sigea.local'],
            [
                'nome' => 'Notificador de Demonstração',
                'password' => 'senha123',
                'categoria_profissional' => 'tecnico_enfermagem',
                'registro_conselho' => 'COREN-SP 654321',
                'cargo' => 'Técnico de Enfermagem',
                'setor_id' => $setores->random(),
                'ativo' => true,
            ]
        );
    }
}
