<?php

namespace Tests\Feature;

use App\Models\EventoAdverso;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_notificador_recebe_403_no_painel(): void
    {
        $this->actingAs($this->criarNotificador())
            ->get('/painel')
            ->assertForbidden();
    }

    public function test_notificador_recebe_403_na_triagem(): void
    {
        $this->actingAs($this->criarNotificador())
            ->get('/triagem')
            ->assertForbidden();
    }

    public function test_notificador_recebe_403_na_administracao(): void
    {
        $notificador = $this->criarNotificador();

        $this->actingAs($notificador)->get('/admin/usuarios')->assertForbidden();
        $this->actingAs($notificador)->get('/admin/setores')->assertForbidden();
        $this->actingAs($notificador)->get('/admin/configuracoes')->assertForbidden();
        $this->actingAs($notificador)->get('/admin/auditoria')->assertForbidden();
    }

    public function test_gestor_recebe_403_na_administracao(): void
    {
        $this->actingAs($this->criarGestor())
            ->get('/admin/usuarios')
            ->assertForbidden();
    }

    public function test_notificador_nao_ve_evento_de_outro_notificador(): void
    {
        [$setor, $categoria] = $this->criarBaseEvento();
        $dono = $this->criarNotificador();
        $intruso = $this->criarNotificador();

        $evento = EventoAdverso::factory()->create([
            'notificante_id' => $dono->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);

        $this->actingAs($intruso)->get(route('eventos.show', $evento))->assertForbidden();
        $this->actingAs($dono)->get(route('eventos.show', $evento))->assertOk();
    }

    public function test_gestor_ve_evento_de_qualquer_notificador(): void
    {
        [$setor, $categoria] = $this->criarBaseEvento();
        $dono = $this->criarNotificador();

        $evento = EventoAdverso::factory()->create([
            'notificante_id' => $dono->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);

        $this->actingAs($this->criarGestor())
            ->get(route('eventos.show', $evento))
            ->assertOk();
    }

    public function test_convidado_e_redirecionado_para_login(): void
    {
        $this->get('/painel')->assertRedirect('/login');
        $this->get('/eventos')->assertRedirect('/login');
    }
}
