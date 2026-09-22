<?php

namespace Tests\Feature;

use App\Enums\ClassificacaoIncidente;
use App\Enums\Evitabilidade;
use App\Enums\GrauDano;
use App\Enums\StatusEvento;
use App\Enums\StatusInvestigacao;
use App\Exceptions\TransicaoInvalidaException;
use App\Models\AcaoCorretiva;
use App\Models\EventoAdverso;
use App\Models\Investigacao;
use App\Services\WorkflowEventoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Máquina de estados do evento (Seção 6.1): transições válidas e inválidas.
 */
class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private WorkflowEventoService $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
        Notification::fake();
        $this->workflow = app(WorkflowEventoService::class);
    }

    private function evento(array $atributos = []): EventoAdverso
    {
        [$setor, $categoria] = $this->criarBaseEvento();

        return EventoAdverso::factory()->create(array_merge([
            'notificante_id' => $this->criarNotificador()->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ], $atributos));
    }

    private function classificado(array $atributos = []): EventoAdverso
    {
        return $this->evento(array_merge([
            'status' => StatusEvento::EmTriagem,
            'gestor_responsavel_id' => $this->criarGestor()->id,
            'classificacao' => ClassificacaoIncidente::EventoAdverso,
            'grau_dano' => GrauDano::Leve,
            'evitabilidade' => Evitabilidade::Evitavel,
            'probabilidade' => 3,
            'severidade' => 3,
        ], $atributos));
    }

    // ── Transições válidas ─────────────────────────────────────────────

    public function test_rascunho_para_notificado(): void
    {
        $evento = $this->evento(['status' => StatusEvento::Rascunho]);

        $this->workflow->transicionar($evento, StatusEvento::Notificado);

        $this->assertSame(StatusEvento::Notificado, $evento->fresh()->status);
        $this->assertDatabaseHas('evento_historico', [
            'evento_adverso_id' => $evento->id,
            'status_anterior' => 'rascunho',
            'status_novo' => 'notificado',
        ]);
    }

    public function test_rascunho_para_cancelado(): void
    {
        $evento = $this->evento(['status' => StatusEvento::Rascunho]);

        $this->workflow->transicionar($evento, StatusEvento::Cancelado);

        $this->assertSame(StatusEvento::Cancelado, $evento->fresh()->status);
    }

    public function test_notificado_para_em_triagem_com_gestor_atribuido(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->evento(['status' => StatusEvento::Notificado]);
        $evento->gestor_responsavel_id = $gestor->id;

        $this->workflow->transicionar($evento, StatusEvento::EmTriagem, $gestor);

        $evento->refresh();
        $this->assertSame(StatusEvento::EmTriagem, $evento->status);
        $this->assertNotNull($evento->triado_em);
    }

    public function test_em_triagem_para_em_investigacao_exige_classificacao_completa(): void
    {
        $evento = $this->classificado();

        $this->workflow->transicionar($evento, StatusEvento::EmInvestigacao);

        $evento->refresh();
        $this->assertSame(StatusEvento::EmInvestigacao, $evento->status);
        $this->assertNotNull($evento->investigado_em);
    }

    public function test_em_triagem_para_concluido_apenas_near_miss_sem_dano(): void
    {
        $evento = $this->classificado([
            'classificacao' => ClassificacaoIncidente::NearMiss,
            'grau_dano' => GrauDano::Nenhum,
        ]);

        $this->workflow->transicionar($evento, StatusEvento::Concluido);

        $this->assertSame(StatusEvento::Concluido, $evento->fresh()->status);
    }

    public function test_em_investigacao_para_aguardando_acao_exige_investigacao_concluida_e_acao(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->classificado(['status' => StatusEvento::EmInvestigacao]);

        Investigacao::factory()->concluida()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $gestor->id,
        ]);
        AcaoCorretiva::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $gestor->id,
        ]);

        $this->workflow->transicionar($evento->fresh(), StatusEvento::AguardandoAcao, $gestor);

        $this->assertSame(StatusEvento::AguardandoAcao, $evento->fresh()->status);
    }

    public function test_aguardando_acao_para_concluido_exige_acoes_tratadas(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->classificado(['status' => StatusEvento::AguardandoAcao]);

        AcaoCorretiva::factory()->concluida()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $gestor->id,
        ]);

        $this->workflow->transicionar($evento, StatusEvento::Concluido, $gestor);

        $evento->refresh();
        $this->assertSame(StatusEvento::Concluido, $evento->status);
        $this->assertNotNull($evento->concluido_em);
    }

    public function test_concluido_pode_ser_reaberto_com_justificativa(): void
    {
        $evento = $this->classificado(['status' => StatusEvento::Concluido]);

        $this->workflow->transicionar($evento, StatusEvento::EmInvestigacao, $this->criarGestor(), 'Ação ineficaz — reabertura para nova análise.');

        $this->assertSame(StatusEvento::EmInvestigacao, $evento->fresh()->status);
    }

    // ── Transições inválidas (mínimo de 5 — Seção 11) ─────────────────

    public function test_rascunho_direto_para_em_triagem_e_rejeitado(): void
    {
        $this->expectException(TransicaoInvalidaException::class);
        $this->workflow->transicionar($this->evento(['status' => StatusEvento::Rascunho]), StatusEvento::EmTriagem);
    }

    public function test_notificado_direto_para_concluido_e_rejeitado(): void
    {
        $this->expectException(TransicaoInvalidaException::class);
        $this->workflow->transicionar($this->evento(['status' => StatusEvento::Notificado]), StatusEvento::Concluido);
    }

    public function test_concluido_para_notificado_e_rejeitado(): void
    {
        $this->expectException(TransicaoInvalidaException::class);
        $this->workflow->transicionar($this->classificado(['status' => StatusEvento::Concluido]), StatusEvento::Notificado);
    }

    public function test_cancelado_nao_transiciona_para_nada(): void
    {
        $this->expectException(TransicaoInvalidaException::class);
        $this->workflow->transicionar($this->evento(['status' => StatusEvento::Cancelado]), StatusEvento::Notificado);
    }

    public function test_em_investigacao_para_concluido_direto_e_rejeitado(): void
    {
        $this->expectException(TransicaoInvalidaException::class);
        $this->workflow->transicionar($this->classificado(['status' => StatusEvento::EmInvestigacao]), StatusEvento::Concluido);
    }

    public function test_conclusao_na_triagem_com_dano_e_rejeitada(): void
    {
        $this->expectException(TransicaoInvalidaException::class);

        $evento = $this->classificado([
            'classificacao' => ClassificacaoIncidente::EventoAdverso,
            'grau_dano' => GrauDano::Grave,
        ]);

        $this->workflow->transicionar($evento, StatusEvento::Concluido);
    }

    public function test_triagem_sem_classificacao_completa_nao_encaminha(): void
    {
        $this->expectException(TransicaoInvalidaException::class);

        $evento = $this->evento([
            'status' => StatusEvento::EmTriagem,
            'probabilidade' => null,
            'severidade' => null,
        ]);

        $this->workflow->transicionar($evento, StatusEvento::EmInvestigacao);
    }

    public function test_cancelamento_na_triagem_exige_justificativa(): void
    {
        $this->expectException(TransicaoInvalidaException::class);
        $this->workflow->transicionar($this->classificado(), StatusEvento::Cancelado, null, null);
    }
}
