<?php

namespace Tests\Feature;

use App\Enums\StatusEvento;
use App\Enums\StatusInvestigacao;
use App\Models\AcaoCorretiva;
use App\Models\EventoAdverso;
use App\Models\Investigacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Investigação de causa raiz: bloqueio de conclusão sem causa raiz e ação (UC09/UC10).
 */
class InvestigacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
        Notification::fake();
    }

    private function investigacaoComEvento(): Investigacao
    {
        [$setor, $categoria] = $this->criarBaseEvento();
        $gestor = $this->criarGestor();

        $evento = EventoAdverso::factory()->classificado()->create([
            'notificante_id' => $this->criarNotificador()->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
            'status' => StatusEvento::EmInvestigacao,
            'gestor_responsavel_id' => $gestor->id,
        ]);

        return Investigacao::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $gestor->id,
        ]);
    }

    public function test_conclusao_bloqueada_sem_causa_raiz(): void
    {
        $investigacao = $this->investigacaoComEvento();
        $gestor = $this->criarGestor();

        // Ação existe, mas nenhuma causa raiz marcada
        AcaoCorretiva::factory()->create([
            'evento_adverso_id' => $investigacao->evento_adverso_id,
            'responsavel_id' => $gestor->id,
        ]);

        $this->actingAs($gestor)->post(route('investigacoes.concluir', $investigacao), [
            'causa_raiz' => 'Síntese qualquer',
            'conclusao' => 'Conclusão qualquer',
        ])->assertSessionHas('erro');

        $this->assertSame(StatusInvestigacao::EmAndamento, $investigacao->fresh()->status);
    }

    public function test_conclusao_bloqueada_sem_acao_corretiva(): void
    {
        $investigacao = $this->investigacaoComEvento();
        $gestor = $this->criarGestor();

        $investigacao->causas()->create([
            'categoria' => 'processo',
            'descricao' => 'Ausência de dupla checagem.',
            'ordem' => 1,
            'eh_causa_raiz' => true,
        ]);

        $this->actingAs($gestor)->post(route('investigacoes.concluir', $investigacao), [
            'causa_raiz' => 'Síntese qualquer',
            'conclusao' => 'Conclusão qualquer',
        ])->assertSessionHas('erro');
    }

    public function test_conclusao_com_causa_raiz_e_acao_move_evento_para_aguardando_acao(): void
    {
        $investigacao = $this->investigacaoComEvento();
        $gestor = $this->criarGestor();

        $investigacao->causas()->create([
            'categoria' => 'processo',
            'descricao' => 'Ausência de dupla checagem.',
            'ordem' => 1,
            'eh_causa_raiz' => true,
        ]);
        AcaoCorretiva::factory()->create([
            'evento_adverso_id' => $investigacao->evento_adverso_id,
            'investigacao_id' => $investigacao->id,
            'responsavel_id' => $gestor->id,
        ]);

        $this->actingAs($gestor)->post(route('investigacoes.concluir', $investigacao), [
            'causa_raiz' => 'Ausência de barreira de verificação no processo.',
            'conclusao' => 'Padronizar o processo e treinar a equipe.',
        ])->assertRedirect();

        $this->assertSame(StatusInvestigacao::Concluida, $investigacao->fresh()->status);
        $this->assertSame(StatusEvento::AguardandoAcao, $investigacao->evento->fresh()->status);
    }

    public function test_evento_nao_pode_ter_duas_investigacoes(): void
    {
        $investigacao = $this->investigacaoComEvento();
        $gestor = $this->criarGestor();

        $this->actingAs($gestor)
            ->get(route('investigacoes.create', $investigacao->evento_adverso_id))
            ->assertNotFound();
    }

    public function test_causas_podem_ser_adicionadas_e_removidas(): void
    {
        $investigacao = $this->investigacaoComEvento();
        $gestor = $this->criarGestor();

        $this->actingAs($gestor)->post(route('causas.store', $investigacao), [
            'categoria' => 'equipamento',
            'descricao' => 'Bomba de infusão sem manutenção preventiva.',
            'eh_causa_raiz' => 1,
        ])->assertRedirect();

        $causa = $investigacao->causas()->first();
        $this->assertTrue($causa->eh_causa_raiz);

        $this->actingAs($gestor)
            ->delete(route('causas.destroy', [$investigacao, $causa]))
            ->assertRedirect();

        $this->assertDatabaseMissing('investigacao_causas', ['id' => $causa->id]);
    }
}
