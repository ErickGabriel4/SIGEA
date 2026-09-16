<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Perfil do usuário autenticado (T23) — dados pessoais e troca de senha.
 */
class PerfilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
    }

    public function test_usuario_autenticado_abre_a_tela_de_perfil(): void
    {
        $usuario = $this->criarNotificador(['nome' => 'Ana Beatriz']);

        $this->actingAs($usuario)->get('/perfil')
            ->assertOk()
            ->assertSee('Ana Beatriz')
            ->assertSee('Alterar senha');
    }

    public function test_usuario_atualiza_os_dados_pessoais(): void
    {
        $usuario = $this->criarNotificador();

        $this->actingAs($usuario)
            ->put('/perfil', [
                'nome' => 'Novo Nome',
                'email' => 'novo.email@sigea.test',
                'telefone' => '(19) 99999-0000',
            ])
            ->assertSessionHas('sucesso')
            ->assertRedirect();

        $usuario->refresh();
        $this->assertSame('Novo Nome', $usuario->nome);
        $this->assertSame('novo.email@sigea.test', $usuario->email);
        $this->assertSame('(19) 99999-0000', $usuario->telefone);
    }

    public function test_email_ja_usado_por_outro_usuario_nao_e_aceito(): void
    {
        $outro = $this->criarNotificador(['email' => 'ocupado@sigea.test']);
        $usuario = $this->criarNotificador();

        $this->actingAs($usuario)
            ->put('/perfil', [
                'nome' => $usuario->nome,
                'email' => $outro->email,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_usuario_altera_a_senha_informando_a_senha_atual(): void
    {
        $usuario = $this->criarNotificador();

        $this->actingAs($usuario)
            ->put('/perfil/senha', [
                'senha_atual' => 'senha123',
                'password' => 'novaSenha456',
                'password_confirmation' => 'novaSenha456',
            ])
            ->assertSessionHas('sucesso');

        $this->assertTrue(Hash::check('novaSenha456', $usuario->refresh()->password));
    }

    public function test_senha_atual_incorreta_impede_a_alteracao(): void
    {
        $usuario = $this->criarNotificador();

        $this->actingAs($usuario)
            ->put('/perfil/senha', [
                'senha_atual' => 'senha-errada',
                'password' => 'novaSenha456',
                'password_confirmation' => 'novaSenha456',
            ])
            ->assertSessionHasErrors('senha_atual');

        $this->assertTrue(Hash::check('senha123', $usuario->refresh()->password));
    }

    public function test_nova_senha_sem_numero_e_rejeitada(): void
    {
        $usuario = $this->criarNotificador();

        $this->actingAs($usuario)
            ->put('/perfil/senha', [
                'senha_atual' => 'senha123',
                'password' => 'somenteletras',
                'password_confirmation' => 'somenteletras',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_visitante_nao_autenticado_e_redirecionado_ao_login(): void
    {
        $this->get('/perfil')->assertRedirect('/login');
    }
}
