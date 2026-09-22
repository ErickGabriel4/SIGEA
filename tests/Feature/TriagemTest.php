<?php

namespace Tests\Feature;

use App\Enums\NivelRisco;
use App\Enums\Prioridade;
use App\Enums\StatusEvento;
use App\Models\EventoAdverso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Triagem: cálculo de score/nível de risco e prazo automático pelo SLA (UC06–UC08).
 */
class TriagemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
        Notification::fake();
    }

    private function eventoEmTriagem(): EventoAdverso
    {
        [$setor, $categoria] = $this->criarBaseEvento();

        return EventoAdverso::factory()->create([
            'notificante_id' => $this->criarNotificador()->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
            'status' => StatusEvento::EmTriagem,
        ]);
    }

    public function test_gestor_assume_evento_da_fila(): void
    {
        [$setor, $categoria] = $this->criarBaseEvento();
        $gestor = $this->criarGestor();

        $evento = EventoAdverso::factory()->create([
            'notificante_id' => $this->criarNotificador()->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
            'status' => StatusEvento::Notificado,
        ]);

        $this->actingAs($gestor)
            ->post(route('triagem.assumir', $evento))
            ->assertRedirect(route('triagem.edit', $evento));

        $evento->refresh();
        $this->assertSame(StatusEvento::EmTriagem, $evento->status);
        $this->assertSame($gestor->id, $evento->gestor_responsavel_id);
    }

    public function test_classificacao_calcula_score_nivel_e_prazo_automaticamente(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoEmTriagem();

        $this->actingAs($gestor)->put(route('triagem.update', $evento), [
            'categoria_evento_id' => $evento->categoria_evento_id,
            'classificacao' => 'evento_adverso',
            'grau_dano' => 'moderado',
            'evitabilidade' => 'evitavel',
            'probabilidade' => 4,
            'severidade' => 4,
        ])->assertSessionHasNoErrors();

        $evento->refresh();
        $this->assertSame(16, $evento->score_risco);
        $this->assertSame(NivelRisco::Extremo, $evento->nivel_risco);
        $this->assertSame(Prioridade::Critica, $evento->prioridade);
        // SLA extremo = 15 dias a partir da notificação
        $this->assertSame(
            $evento->created_at->copy()->addDays(15)->toDateString(),
            $evento->prazo_conclusao->toDateString()
        );
    }

    public function test_dano_grave_gera_prioridade_critica_mesmo_com_score_baixo(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoEmTriagem();

        $this->actingAs($gestor)->put(route('triagem.update', $evento), [
            'categoria_evento_id' => $evento->categoria_evento_id,
            'classificacao' => 'evento_adverso',
            'grau_dano' => 'grave',
            'evitabilidade' => 'evitavel',
            'probabilidade' => 1,
            'severidade' => 2,
        ]);

        $evento->refresh();
        $this->assertSame(NivelRisco::Baixo, $evento->nivel_risco);
        $this->assertSame(Prioridade::Critica, $evento->prioridade);
        // Dano grave torna o evento elegível ao NOTIVISA
        $this->assertTrue($evento->notificavel_anvisa);
    }

    public function test_probabilidade_fora_da_escala_e_rejeitada(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoEmTriagem();

        $this->actingAs($gestor)->put(route('triagem.update', $evento), [
            'categoria_evento_id' => $evento->categoria_evento_id,
            'classificacao' => 'evento_adverso',
            'grau_dano' => 'leve',
            'evitabilidade' => 'evitavel',
            'probabilidade' => 6,
            'severidade' => 0,
        ])->assertSessionHasErrors(['probabilidade', 'severidade']);
    }

    public function test_feedback_e_registrado_para_o_notificante(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoEmTriagem();

        $this->actingAs($gestor)->post(route('triagem.feedback', $evento), [
            'feedback_notificante' => 'Obrigado pela notificação. Medidas preventivas foram adotadas.',
        ])->assertRedirect();

        $this->assertStringContainsString('Medidas preventivas', $evento->fresh()->feedback_notificante);
    }

    public function test_registro_de_protocolo_notivisa(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoEmTriagem();
        $evento->update(['notificavel_anvisa' => true]);

        $this->actingAs($gestor)->post(route('triagem.notivisa', $evento), [
            'protocolo_notivisa' => 'NTV-123456',
            'notificado_anvisa_em' => today()->toDateString(),
        ])->assertRedirect();

        $this->assertSame('NTV-123456', $evento->fresh()->protocolo_notivisa);
        $this->assertFalse($evento->fresh()->pendenteNotivisa());
    }
}
