<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Autenticação: login, throttle, logout e ausência de registro público (Seção 11).
 */
class AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_tela_de_login_e_exibida(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Acesse com suas credenciais institucionais');
    }

    public function test_login_valido_redireciona_para_o_painel(): void
    {
        $usuario = User::factory()->create(['password' => 'senha123']);

        $resposta = $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'senha123',
        ]);

        $resposta->assertRedirect(route('painel.index', absolute: false));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_login_invalido_exibe_mensagem_generica(): void
    {
        $usuario = User::factory()->create(['password' => 'senha123']);

        $resposta = $this->from('/login')->post('/login', [
            'email' => $usuario->email,
            'password' => 'senha-errada',
        ]);

        $resposta->assertRedirect('/login');
        $resposta->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_usuario_inativo_nao_autentica(): void
    {
        $usuario = User::factory()->inativo()->create(['password' => 'senha123']);

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'senha123',
        ]);

        $this->assertGuest();
    }

    public function test_nao_existe_rota_de_registro_publico(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_logout_encerra_a_sessao(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_painel_exige_autenticacao(): void
    {
        $this->get('/painel')->assertRedirect('/login');
    }

    public function test_ultimo_acesso_e_registrado_na_navegacao(): void
    {
        $usuario = User::factory()->create(['ultimo_acesso_em' => null]);

        $this->actingAs($usuario)->get('/painel')->assertOk();

        $this->assertNotNull($usuario->fresh()->ultimo_acesso_em);
    }
}
