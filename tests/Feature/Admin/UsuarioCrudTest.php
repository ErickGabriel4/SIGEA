<?php

namespace Tests\Feature\Admin;

use App\Enums\CategoriaProfissional;
use App\Models\Setor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Gestão de usuários e perfis (T17 — UC18).
 */
class UsuarioCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
    }

    public function test_administrador_lista_e_abre_os_formularios_de_usuario(): void
    {
        $administrador = $this->criarAdministrador();
        $notificador = $this->criarNotificador(['nome' => 'Joana Enfermeira']);

        $this->actingAs($administrador)->get('/admin/usuarios')
            ->assertOk()
            ->assertSee('Joana Enfermeira');

        $this->actingAs($administrador)->get('/admin/usuarios/create')->assertOk();
        $this->actingAs($administrador)->get(route('admin.usuarios.edit', $notificador))->assertOk();
    }

    public function test_busca_filtra_por_nome_email_ou_matricula(): void
    {
        $administrador = $this->criarAdministrador(['nome' => 'Administrador Geral']);
        $this->criarNotificador(['nome' => 'Carlos Técnico', 'matricula' => 'M-9001']);

        $this->actingAs($administrador)->get('/admin/usuarios?busca=M-9001')
            ->assertOk()
            ->assertSee('Carlos Técnico')
            ->assertDontSee('Administrador Geral');
    }

    public function test_filtro_por_perfil_lista_apenas_o_perfil_escolhido(): void
    {
        $administrador = $this->criarAdministrador();
        $this->criarGestor(['nome' => 'Gestora do NSP']);
        $this->criarNotificador(['nome' => 'Notificador Comum']);

        $this->actingAs($administrador)->get('/admin/usuarios?perfil=gestor_seguranca')
            ->assertOk()
            ->assertSee('Gestora do NSP')
            ->assertDontSee('Notificador Comum');
    }

    public function test_administrador_cadastra_usuario_com_perfil(): void
    {
        $setor = Setor::factory()->create();

        $this->actingAs($this->criarAdministrador())
            ->post('/admin/usuarios', [
                'nome' => 'Marina Farmacêutica',
                'email' => 'marina@hospital.test',
                'password' => 'senha12345',
                'password_confirmation' => 'senha12345',
                'matricula' => 'M-7788',
                'categoria_profissional' => CategoriaProfissional::Farmaceutico->value,
                'setor_id' => $setor->id,
                'perfil' => 'notificador',
                'ativo' => 1,
            ])
            ->assertRedirect(route('admin.usuarios.index'))
            ->assertSessionHas('sucesso');

        $usuario = User::where('email', 'marina@hospital.test')->firstOrFail();

        $this->assertTrue($usuario->hasRole('notificador'));
        $this->assertTrue(Hash::check('senha12345', $usuario->password));
    }

    public function test_email_duplicado_e_senha_fraca_sao_rejeitados(): void
    {
        $existente = $this->criarNotificador();

        $this->actingAs($this->criarAdministrador())
            ->post('/admin/usuarios', [
                'nome' => 'Repetido',
                'email' => $existente->email,
                'password' => '123',
                'password_confirmation' => '123',
                'categoria_profissional' => CategoriaProfissional::Outro->value,
                'perfil' => 'notificador',
            ])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_edicao_sem_senha_mantem_a_senha_atual_e_troca_o_perfil(): void
    {
        $usuario = $this->criarNotificador(['password' => 'senhaOriginal1']);

        $this->actingAs($this->criarAdministrador())
            ->put(route('admin.usuarios.update', $usuario), [
                'nome' => 'Nome Atualizado',
                'email' => $usuario->email,
                'password' => '',
                'password_confirmation' => '',
                'categoria_profissional' => $usuario->categoria_profissional->value,
                'perfil' => 'gestor_seguranca',
                'ativo' => 1,
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $usuario->refresh();

        $this->assertSame('Nome Atualizado', $usuario->nome);
        $this->assertTrue($usuario->hasRole('gestor_seguranca'));
        $this->assertFalse($usuario->hasRole('notificador'));
        $this->assertTrue(Hash::check('senhaOriginal1', $usuario->password));
    }

    public function test_administrador_suspende_e_reativa_acesso(): void
    {
        $administrador = $this->criarAdministrador();
        $usuario = $this->criarNotificador(['ativo' => true]);

        $this->actingAs($administrador)
            ->post(route('admin.usuarios.alternar-status', $usuario))
            ->assertRedirect();

        $this->assertFalse($usuario->refresh()->ativo);

        $this->actingAs($administrador)
            ->post(route('admin.usuarios.alternar-status', $usuario));

        $this->assertTrue($usuario->refresh()->ativo);
    }

    public function test_administrador_nao_inativa_nem_exclui_a_si_mesmo(): void
    {
        $administrador = $this->criarAdministrador();

        $this->actingAs($administrador)
            ->post(route('admin.usuarios.alternar-status', $administrador))
            ->assertForbidden();

        $this->actingAs($administrador)
            ->delete(route('admin.usuarios.destroy', $administrador))
            ->assertForbidden();

        $this->assertTrue($administrador->refresh()->ativo);
    }

    public function test_exclusao_de_usuario_e_logica(): void
    {
        $usuario = $this->criarNotificador();

        $this->actingAs($this->criarAdministrador())
            ->delete(route('admin.usuarios.destroy', $usuario))
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertSoftDeleted('users', ['id' => $usuario->id, 'ativo' => false]);
    }

    public function test_gestor_e_notificador_nao_acessam_a_gestao_de_usuarios(): void
    {
        $this->actingAs($this->criarGestor())->get('/admin/usuarios')->assertForbidden();
        $this->actingAs($this->criarNotificador())->get('/admin/usuarios')->assertForbidden();
    }
}
