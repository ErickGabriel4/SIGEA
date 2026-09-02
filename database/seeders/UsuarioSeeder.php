<?php

namespace Database\Seeders;

use App\Models\Setor;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 30 usuários simulados: 24 notificadores, 4 gestores, 2 administradores,
 * incluindo os 3 usuários fixos de demonstração (Seção 10).
 */
class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $setores = Setor::pluck('id');

        // ── Usuários fixos de demonstração ─────────────────────────────
        $admin = User::firstOrCreate(
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
        $admin->syncRoles(['administrador']);

        $gestor = User::firstOrCreate(
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
        $gestor->syncRoles(['gestor_seguranca']);

        $notificador = User::firstOrCreate(
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
        $notificador->syncRoles(['notificador']);

        // ── Massa complementar: 23 notificadores, 3 gestores, 1 admin ──
        User::factory(23)->create()->each(function (User $usuario) use ($setores) {
            $usuario->update(['setor_id' => $setores->random()]);
            $usuario->syncRoles(['notificador']);
        });

        User::factory(3)->create()->each(function (User $usuario) use ($setores) {
            $usuario->update(['setor_id' => $setores->random(), 'categoria_profissional' => 'enfermeiro']);
            $usuario->syncRoles(['gestor_seguranca']);
        });

        User::factory(1)->create()->each(function (User $usuario) use ($setores) {
            $usuario->update(['setor_id' => $setores->random(), 'categoria_profissional' => 'administrativo']);
            $usuario->syncRoles(['administrador']);
        });
    }
}
