<?php

namespace Tests\Feature;

use App\Enums\StatusAcao;
use App\Models\AcaoCorretiva;
use App\Models\EventoAdverso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Ações corretivas: evidência obrigatória e job de atraso (US21/US23).
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

    private function acaoDoResponsavel($responsavel): AcaoCorretiva
    {
        [$setor, $categoria] = $this->criarBaseEvento();

        $evento = EventoAdverso::factory()->create([
            'notificante_id' => $this->criarNotificador()->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);

        return AcaoCorretiva::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $responsavel->id,
        ]);
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

    public function test_job_marca_acoes_vencidas_como_atrasadas(): void
    {
        $responsavel = $this->criarNotificador();
        [$setor, $categoria] = $this->criarBaseEvento();

        $evento = EventoAdverso::factory()->create([
            'notificante_id' => $responsavel->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);

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

    public function test_gestor_avalia_eficacia_apenas_de_acao_concluida(): void
    {
        $gestor = $this->criarGestor();
        $responsavel = $this->criarNotificador();
        $acao = $this->acaoDoResponsavel($responsavel);

        // Pendente: avaliação negada
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
}
