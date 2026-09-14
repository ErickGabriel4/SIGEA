<?php

namespace Tests\Feature\Eventos;

use App\Enums\ClassificacaoIncidente;
use App\Enums\Evitabilidade;
use App\Enums\GrauDano;
use App\Enums\NivelRisco;
use App\Enums\StatusEvento;
use App\Models\CategoriaEvento;
use App\Models\EventoAdverso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Triagem, classificação e matriz de risco do evento adverso (T09/T10).
 */
class TriagemTest extends TestCase
{
    use RefreshDatabase;

    private CategoriaEvento $categoria;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();

        $raiz = CategoriaEvento::factory()->create(['codigo' => 'MED', 'notificavel_anvisa' => false]);
        $this->categoria = CategoriaEvento::factory()->filhaDe($raiz->id)->create([
            'codigo' => 'MED.01',
            'exige_investigacao' => false,
            'notificavel_anvisa' => false,
        ]);
    }

    private function eventoNotificado(array $atributos = []): EventoAdverso
    {
        return EventoAdverso::factory()->create([
            'categoria_evento_id' => $this->categoria->id,
            'status' => StatusEvento::Notificado,
            ...$atributos,
        ]);
    }

    private function dadosClassificacao(array $extras = []): array
    {
        return array_merge([
            'categoria_evento_id' => $this->categoria->id,
            'classificacao' => ClassificacaoIncidente::EventoAdverso->value,
            'grau_dano' => GrauDano::Moderado->value,
            'evitabilidade' => Evitabilidade::Evitavel->value,
            'probabilidade' => 4,
            'severidade' => 4,
        ], $extras);
    }

    public function test_fila_lista_apenas_eventos_notificados(): void
    {
        $gestor = $this->criarGestor();
        $notificado = $this->eventoNotificado();
        $rascunho = EventoAdverso::factory()->rascunho()->create(['categoria_evento_id' => $this->categoria->id]);

        $resposta = $this->actingAs($gestor)->get(route('triagem.index'));

        $resposta->assertOk();
        $resposta->assertSee($notificado->protocolo);
        $resposta->assertDontSee($rascunho->protocolo);
    }

    public function test_notificador_nao_acessa_a_fila_de_triagem(): void
    {
        $notificador = $this->criarNotificador();

        $this->actingAs($notificador)->get(route('triagem.index'))->assertForbidden();
    }

    public function test_gestor_assume_evento_notificado(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoNotificado();

        $this->actingAs($gestor)
            ->post(route('triagem.assumir', $evento))
            ->assertRedirect(route('triagem.edit', $evento));

        $evento->refresh();
        $this->assertSame(StatusEvento::EmTriagem, $evento->status);
        $this->assertSame($gestor->id, $evento->gestor_responsavel_id);
        $this->assertNotNull($evento->triado_em);
        $this->assertTrue($evento->historico()->where('acao', 'assumir_triagem')->exists());
    }

    public function test_tela_de_classificacao_renderiza_para_o_gestor(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoNotificado(['gestor_responsavel_id' => $gestor->id, 'status' => StatusEvento::EmTriagem]);

        $this->actingAs($gestor)
            ->get(route('triagem.edit', $evento))
            ->assertOk()
            ->assertSee($evento->protocolo);
    }

    public function test_classificacao_aplica_matriz_de_risco(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoNotificado(['gestor_responsavel_id' => $gestor->id, 'status' => StatusEvento::EmTriagem]);

        $this->actingAs($gestor)
            ->put(route('triagem.update', $evento), $this->dadosClassificacao(['probabilidade' => 5, 'severidade' => 5]))
            ->assertRedirect();

        $evento->refresh();
        $this->assertSame(NivelRisco::Extremo, $evento->nivel_risco);
        $this->assertNotNull($evento->prazo_conclusao);
        $this->assertTrue($evento->classificacaoCompleta());
    }

    public function test_dano_grave_forca_prioridade_critica(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoNotificado(['gestor_responsavel_id' => $gestor->id, 'status' => StatusEvento::EmTriagem]);

        $this->actingAs($gestor)->put(route('triagem.update', $evento), $this->dadosClassificacao([
            'grau_dano' => GrauDano::Grave->value,
            'probabilidade' => 1,
            'severidade' => 1,
        ]));

        $this->assertSame('critica', $evento->fresh()->prioridade->value);
    }

    public function test_notificador_nao_classifica_evento(): void
    {
        $notificador = $this->criarNotificador();
        $gestor = $this->criarGestor();
        $evento = $this->eventoNotificado(['gestor_responsavel_id' => $gestor->id, 'status' => StatusEvento::EmTriagem]);

        $this->actingAs($notificador)
            ->put(route('triagem.update', $evento), $this->dadosClassificacao())
            ->assertForbidden();
    }

    public function test_encaminhar_para_investigacao_exige_classificacao_completa(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoNotificado(['gestor_responsavel_id' => $gestor->id, 'status' => StatusEvento::EmTriagem]);

        $this->actingAs($gestor)
            ->post(route('triagem.encaminhar', $evento), ['destino' => 'em_investigacao'])
            ->assertRedirect();

        $this->assertSame(StatusEvento::EmTriagem, $evento->fresh()->status);
        $this->assertNotNull(session('erro'));
    }

    public function test_encaminhar_para_investigacao_apos_classificacao(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoNotificado(['gestor_responsavel_id' => $gestor->id, 'status' => StatusEvento::EmTriagem]);
        $this->actingAs($gestor)->put(route('triagem.update', $evento), $this->dadosClassificacao());

        $this->actingAs($gestor)
            ->post(route('triagem.encaminhar', $evento), ['destino' => 'em_investigacao'])
            ->assertRedirect(route('eventos.show', $evento));

        $evento->refresh();
        $this->assertSame(StatusEvento::EmInvestigacao, $evento->status);
        $this->assertNotNull($evento->investigado_em);
    }

    public function test_categoria_que_exige_investigacao_nao_permite_dispensa(): void
    {
        $gestor = $this->criarGestor();
        $categoriaExige = CategoriaEvento::factory()->filhaDe($this->categoria->categoria_pai_id ?? CategoriaEvento::factory()->create()->id)
            ->create(['exige_investigacao' => true]);
        $evento = $this->eventoNotificado([
            'categoria_evento_id' => $categoriaExige->id,
            'gestor_responsavel_id' => $gestor->id,
            'status' => StatusEvento::EmTriagem,
        ]);
        $this->actingAs($gestor)->put(route('triagem.update', $evento), $this->dadosClassificacao(['categoria_evento_id' => $categoriaExige->id]));

        $this->actingAs($gestor)
            ->post(route('triagem.encaminhar', $evento), ['destino' => 'aguardando_acao'])
            ->assertRedirect();

        $this->assertSame(StatusEvento::EmTriagem, $evento->fresh()->status);
    }

    public function test_conclusao_direta_permitida_para_near_miss_sem_dano(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoNotificado(['gestor_responsavel_id' => $gestor->id, 'status' => StatusEvento::EmTriagem]);
        $this->actingAs($gestor)->put(route('triagem.update', $evento), $this->dadosClassificacao([
            'classificacao' => ClassificacaoIncidente::NearMiss->value,
            'grau_dano' => GrauDano::Nenhum->value,
        ]));

        $this->actingAs($gestor)
            ->post(route('triagem.concluir', $evento))
            ->assertRedirect(route('eventos.show', $evento));

        $evento->refresh();
        $this->assertSame(StatusEvento::Concluido, $evento->status);
        $this->assertNotNull($evento->concluido_em);
    }

    public function test_conclusao_direta_rejeitada_com_dano(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoNotificado(['gestor_responsavel_id' => $gestor->id, 'status' => StatusEvento::EmTriagem]);
        $this->actingAs($gestor)->put(route('triagem.update', $evento), $this->dadosClassificacao());

        $this->actingAs($gestor)->post(route('triagem.concluir', $evento))->assertRedirect();

        $this->assertSame(StatusEvento::EmTriagem, $evento->fresh()->status);
    }

    public function test_cancelamento_na_triagem_exige_justificativa(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoNotificado(['gestor_responsavel_id' => $gestor->id, 'status' => StatusEvento::EmTriagem]);

        $this->actingAs($gestor)
            ->post(route('eventos.cancelar', $evento), [])
            ->assertSessionHasErrors('justificativa');

        $this->actingAs($gestor)
            ->post(route('eventos.cancelar', $evento), ['justificativa' => 'Duplicidade do EA-2026-000123'])
            ->assertRedirect(route('eventos.show', $evento));

        $this->assertSame(StatusEvento::Cancelado, $evento->fresh()->status);
    }

    public function test_devolutiva_registra_feedback_ao_notificante(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoNotificado(['gestor_responsavel_id' => $gestor->id, 'status' => StatusEvento::EmTriagem]);

        $this->actingAs($gestor)
            ->post(route('triagem.feedback', $evento), ['feedback_notificante' => 'Obrigado pela notificação.'])
            ->assertRedirect();

        $this->assertSame('Obrigado pela notificação.', $evento->fresh()->feedback_notificante);
    }

    public function test_registro_de_protocolo_notivisa(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoNotificado(['gestor_responsavel_id' => $gestor->id, 'status' => StatusEvento::EmTriagem]);
        $evento->update(['notificavel_anvisa' => true]);

        $this->actingAs($gestor)->post(route('triagem.notivisa', $evento), [
            'protocolo_notivisa' => 'NTV-123456',
            'notificado_anvisa_em' => today()->toDateString(),
        ])->assertRedirect();

        $this->assertSame('NTV-123456', $evento->fresh()->protocolo_notivisa);
        $this->assertFalse($evento->fresh()->pendenteNotivisa());
    }
}
