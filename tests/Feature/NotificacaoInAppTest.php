<?php

namespace Tests\Feature;

use App\Models\AcaoCorretiva;
use App\Notifications\AcaoCorretivaAtribuida;
use Tests\TestCase;

/**
 * Notificações in-app (T22): listagem, contador do sino e marcação de leitura.
 */
class NotificacaoInAppTest extends TestCase
{
    public function test_listagem_mostra_notificacoes_do_usuario(): void
    {
        $this->semearPerfis();
        $notificador = $this->criarNotificador();
        $acao = AcaoCorretiva::factory()->create(['responsavel_id' => $notificador->id]);

        $notificador->notify(new AcaoCorretivaAtribuida($acao));

        $this->actingAs($notificador)
            ->get(route('notificacoes.index'))
            ->assertOk()
            ->assertSee('Ação corretiva atribuída');
    }

    public function test_contador_reflete_notificacoes_nao_lidas(): void
    {
        $this->semearPerfis();
        $notificador = $this->criarNotificador();
        $acao = AcaoCorretiva::factory()->create(['responsavel_id' => $notificador->id]);

        $notificador->notify(new AcaoCorretivaAtribuida($acao));

        $this->actingAs($notificador)
            ->getJson(route('notificacoes.contador'))
            ->assertOk()
            ->assertJson(['label' => '1']);
    }

    public function test_marcar_lida_redireciona_para_url_da_notificacao(): void
    {
        $this->semearPerfis();
        $notificador = $this->criarNotificador();
        $acao = AcaoCorretiva::factory()->create(['responsavel_id' => $notificador->id]);

        $notificador->notify(new AcaoCorretivaAtribuida($acao));
        $notificacao = $notificador->notifications()->first();

        $this->actingAs($notificador)
            ->post(route('notificacoes.lida', $notificacao->id))
            ->assertRedirect(route('acoes.minhas'));

        $this->assertNotNull($notificacao->fresh()->read_at);
    }

    public function test_marcar_todas_zera_o_contador(): void
    {
        $this->semearPerfis();
        $notificador = $this->criarNotificador();
        AcaoCorretiva::factory()->count(3)->create(['responsavel_id' => $notificador->id])
            ->each(fn (AcaoCorretiva $acao) => $notificador->notify(new AcaoCorretivaAtribuida($acao)));

        $this->actingAs($notificador)
            ->post(route('notificacoes.todas'))
            ->assertRedirect();

        $this->assertSame(0, $notificador->unreadNotifications()->count());
    }

    public function test_visitante_nao_acessa_notificacoes(): void
    {
        $this->get(route('notificacoes.index'))->assertRedirect(route('login'));
    }
}
