<?php

namespace Tests\Feature\Eventos;

use App\Enums\StatusAcao;
use App\Enums\StatusEvento;
use App\Models\AcaoCorretiva;
use App\Models\EventoAdverso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Ações corretivas: cadastro, conclusão com evidência, avaliação de eficácia
 * e a transição do evento de aguardando_acao para concluído.
 */
class AcaoCorretivaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
        Notification::fake();
    }

    private function eventoAguardandoAcao(array $atributos = []): EventoAdverso
    {
        return EventoAdverso::factory()->create([
            'status' => StatusEvento::AguardandoAcao,
            ...$atributos,
        ]);
    }

    private function acaoDoResponsavel($responsavel, array $atributos = []): AcaoCorretiva
    {
        $evento = $this->eventoAguardandoAcao();

        return AcaoCorretiva::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $responsavel->id,
            ...$atributos,
        ]);
    }

    public function test_gestor_cadastra_acao_e_notifica_responsavel(): void
    {
        $gestor = $this->criarGestor();
        $responsavel = $this->criarNotificador();
        $evento = $this->eventoAguardandoAcao();

        $resposta = $this->actingAs($gestor)->post(route('acoes.store', $evento), [
            'titulo' => 'Padronizar dupla checagem',
            'descricao' => 'Implantar dupla checagem na dispensação de medicamentos.',
            'tipo' => 'barreira',
            'responsavel_id' => $responsavel->id,
            'prazo' => today()->addDays(30)->toDateString(),
        ]);

        $resposta->assertRedirect(route('eventos.show', ['evento' => $evento, 'aba' => 'acoes']));

        $acao = AcaoCorretiva::first();
        $this->assertNotNull($acao);
        $this->assertSame($evento->id, $acao->evento_adverso_id);
        $this->assertSame(StatusAcao::Pendente, $acao->status);

        Notification::assertSentTo($responsavel, \App\Notifications\AcaoCorretivaAtribuida::class);
    }

    public function test_notificador_nao_cadastra_acao(): void
    {
        $notificador = $this->criarNotificador();
        $evento = $this->eventoAguardandoAcao();

        $this->actingAs($notificador)
            ->get(route('acoes.create', $evento))
            ->assertForbidden();
    }

    public function test_conclusao_sem_evidencia_e_rejeitada(): void
    {
        $responsavel = $this->criarNotificador();
        $acao = $this->acaoDoResponsavel($responsavel);

        $this->actingAs($responsavel)
            ->post(route('acoes.concluir', $acao), ['evidencia_conclusao' => ''])
            ->assertSessionHasErrors('evidencia_conclusao');

        $this->assertSame(StatusAcao::Pendente, $acao->fresh()->status);
    }

    public function test_conclusao_com_evidencia_marca_como_concluida(): void
    {
        $responsavel = $this->criarNotificador();
        $acao = $this->acaoDoResponsavel($responsavel);

        $this->actingAs($responsavel)->post(route('acoes.concluir', $acao), [
            'evidencia_conclusao' => 'Treinamento realizado em 20/08 — ata arquivada no NSP.',
        ])->assertRedirect();

        $acao->refresh();
        $this->assertSame(StatusAcao::Concluida, $acao->status);
        $this->assertNotNull($acao->data_conclusao);
    }

    public function test_apenas_responsavel_ou_gestor_conclui(): void
    {
        $responsavel = $this->criarNotificador();
        $outro = $this->criarNotificador();
        $acao = $this->acaoDoResponsavel($responsavel);

        $this->actingAs($outro)->post(route('acoes.concluir', $acao), [
            'evidencia_conclusao' => 'Tentativa indevida de conclusão.',
        ])->assertForbidden();
    }

    public function test_gestor_avalia_eficacia_apenas_de_acao_concluida(): void
    {
        $gestor = $this->criarGestor();
        $responsavel = $this->criarNotificador();
        $acao = $this->acaoDoResponsavel($responsavel);

        $this->actingAs($gestor)
            ->post(route('acoes.avaliar', $acao), ['eficacia' => 'eficaz'])
            ->assertForbidden();

        $acao->update([
            'status' => StatusAcao::Concluida,
            'data_conclusao' => today(),
            'evidencia_conclusao' => 'Evidência registrada.',
        ]);

        $this->actingAs($gestor)
            ->post(route('acoes.avaliar', $acao), ['eficacia' => 'eficaz'])
            ->assertRedirect();

        $this->assertSame('eficaz', $acao->fresh()->eficacia->value);
    }

    public function test_job_marca_acoes_vencidas_como_atrasadas(): void
    {
        $responsavel = $this->criarNotificador();
        $evento = $this->eventoAguardandoAcao();

        $vencida = AcaoCorretiva::factory()->vencida()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $responsavel->id,
        ]);
        $noPrazo = AcaoCorretiva::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $responsavel->id,
            'prazo' => today()->addMonth()->toDateString(),
        ]);
        $concluidaVencida = AcaoCorretiva::factory()->vencida()->concluida()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $responsavel->id,
        ]);

        $this->artisan('sigea:marcar-acoes-atrasadas')->assertSuccessful();

        $this->assertSame(StatusAcao::Atrasada, $vencida->fresh()->status);
        $this->assertSame(StatusAcao::Pendente, $noPrazo->fresh()->status);
        $this->assertSame(StatusAcao::Concluida, $concluidaVencida->fresh()->status);
    }

    public function test_evento_nao_conclui_com_acao_pendente(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoAguardandoAcao();
        AcaoCorretiva::factory()->create(['evento_adverso_id' => $evento->id, 'responsavel_id' => $gestor->id]);

        $this->actingAs($gestor)
            ->post(route('triagem.concluir', $evento))
            ->assertRedirect();

        $this->assertSame(StatusEvento::AguardandoAcao, $evento->fresh()->status);
    }

    public function test_evento_conclui_quando_todas_as_acoes_estao_tratadas(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoAguardandoAcao();
        AcaoCorretiva::factory()->concluida()->create(['evento_adverso_id' => $evento->id, 'responsavel_id' => $gestor->id]);
        AcaoCorretiva::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $gestor->id,
            'status' => StatusAcao::Cancelada,
        ]);

        $this->actingAs($gestor)
            ->post(route('triagem.concluir', $evento))
            ->assertRedirect(route('eventos.show', $evento));

        $this->assertSame(StatusEvento::Concluido, $evento->fresh()->status);
    }
}
