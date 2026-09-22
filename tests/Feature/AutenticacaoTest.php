<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Autenticação: login, throttle, logout e ausência de registro público (Seção 11).
 */
class AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
    }

    public function test_login_valido_redireciona_conforme_perfil(): void
    {
        $gestor = $this->criarGestor(['password' => 'senha123']);

        $resposta = $this->post('/login', [
            'email' => $gestor->email,
            'password' => 'senha123',
        ]);

        $resposta->assertRedirect(route('painel.index', absolute: false));
        $this->assertAuthenticatedAs($gestor);
    }

    public function test_notificador_redireciona_para_minhas_notificacoes(): void
    {
        $notificador = $this->criarNotificador(['password' => 'senha123']);

        $resposta = $this->post('/login', [
            'email' => $notificador->email,
            'password' => 'senha123',
        ]);

        $resposta->assertRedirect(route('eventos.meus', absolute: false));
    }

    public function test_login_invalido_exibe_mensagem_generica(): void
    {
        $usuario = $this->criarNotificador();

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
        $usuario = $this->criarNotificador(['password' => 'senha123', 'ativo' => false]);

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'senha123',
        ]);

        $this->assertGuest();
    }

    public function test_cinco_tentativas_falhas_bloqueiam_temporariamente(): void
    {
        $usuario = $this->criarNotificador();

        foreach (range(1, 5) as $tentativa) {
            $this->post('/login', [
                'email' => $usuario->email,
                'password' => 'senha-errada',
            ]);
        }

        $resposta = $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'senha-errada',
        ]);

        // Bloqueio pelo middleware throttle:5,1 da rota (HTTP 429 — Too Many Requests)
        $resposta->assertTooManyRequests();
        $this->assertGuest();
    }

    public function test_logout_encerra_sessao(): void
    {
        $usuario = $this->criarNotificador();

        $this->actingAs($usuario)->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_nao_existe_rota_de_registro_publico(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }
}
