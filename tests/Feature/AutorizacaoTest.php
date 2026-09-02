<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PerfilSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Autorização RBAC: negação por padrão (Seção 3 / Seção 11).
 */
class AutorizacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
    }

    public function test_os_tres_perfis_sao_criados(): void
    {
        $this->assertEqualsCanonicalizing(
            ['notificador', 'gestor_seguranca', 'administrador'],
            Role::pluck('name')->all()
        );
    }

    public function test_administrador_acumula_todas_as_permissoes(): void
    {
        $administrador = $this->criarAdministrador();

        foreach (array_keys(PerfilSeeder::PERMISSOES) as $permissao) {
            $this->assertTrue(
                $administrador->can($permissao),
                "Administrador deveria ter a permissão {$permissao}."
            );
        }
    }

    public function test_notificador_so_registra_e_acompanha_as_proprias_notificacoes(): void
    {
        $notificador = $this->criarNotificador();

        $this->assertTrue($notificador->can('evento.criar'));
        $this->assertTrue($notificador->can('evento.ver_proprios'));
        $this->assertFalse($notificador->can('evento.ver_todos'));
        $this->assertFalse($notificador->can('evento.triar'));
        $this->assertFalse($notificador->can('usuario.gerenciar'));
    }

    public function test_gestor_tria_eventos_mas_nao_administra_o_sistema(): void
    {
        $gestor = $this->criarGestor();

        $this->assertTrue($gestor->can('evento.triar'));
        $this->assertTrue($gestor->can('evento.ver_todos'));
        $this->assertTrue($gestor->can('dashboard.ver'));
        $this->assertFalse($gestor->can('usuario.gerenciar'));
        $this->assertFalse($gestor->can('auditoria.ver'));
    }

    public function test_notificador_recebe_403_no_painel(): void
    {
        $this->actingAs($this->criarNotificador())
            ->get('/painel')
            ->assertForbidden()
            ->assertSee('Acesso negado');
    }

    public function test_gestor_acessa_o_painel(): void
    {
        $this->actingAs($this->criarGestor())
            ->get('/painel')
            ->assertOk();
    }

    public function test_usuario_sem_perfil_recebe_403_no_painel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/painel')
            ->assertForbidden();
    }

    public function test_convidado_e_redirecionado_para_login(): void
    {
        $this->get('/painel')->assertRedirect('/login');
    }
}
