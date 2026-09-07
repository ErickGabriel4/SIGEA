<?php

namespace Tests\Feature\Eventos;

use App\Enums\StatusEvento;
use App\Enums\StatusInvestigacao;
use App\Models\CategoriaEvento;
use App\Models\EventoAdverso;
use App\Models\Investigacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Investigação e análise de causa raiz (T11/T12).
 */
class InvestigacaoTest extends TestCase
{
    use RefreshDatabase;

    private CategoriaEvento $categoria;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();

        $raiz = CategoriaEvento::factory()->create(['codigo' => 'MED']);
        $this->categoria = CategoriaEvento::factory()->filhaDe($raiz->id)->create(['codigo' => 'MED.01']);
    }

    private function eventoEmInvestigacao(array $atributos = []): EventoAdverso
    {
        return EventoAdverso::factory()->create([
            'categoria_evento_id' => $this->categoria->id,
            'status' => StatusEvento::EmInvestigacao,
            ...$atributos,
        ]);
    }

    private function dadosAbertura(array $extras = []): array
    {
        return array_merge([
            'responsavel_id' => $this->criarGestor()->id,
            'metodo' => 'ishikawa',
            'data_inicio' => today()->toDateString(),
            'data_prevista' => today()->addDays(30)->toDateString(),
        ], $extras);
    }

    public function test_gestor_abre_investigacao_para_evento_em_investigacao(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoEmInvestigacao();

        $resposta = $this->actingAs($gestor)
            ->post(route('investigacoes.store', $evento), $this->dadosAbertura(['responsavel_id' => $gestor->id]));

        $investigacao = Investigacao::first();
        $resposta->assertRedirect(route('investigacoes.show', $investigacao));
        $this->assertSame($evento->id, $investigacao->evento_adverso_id);
        $this->assertSame(StatusInvestigacao::EmAndamento, $investigacao->status);
    }

    public function test_nao_permite_segunda_investigacao_para_o_mesmo_evento(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoEmInvestigacao();
        Investigacao::factory()->create(['evento_adverso_id' => $evento->id]);

        $this->actingAs($gestor)
            ->get(route('investigacoes.create', $evento))
            ->assertNotFound();
    }

    public function test_notificador_nao_abre_investigacao(): void
    {
        $notificador = $this->criarNotificador();
        $evento = $this->eventoEmInvestigacao();

        $this->actingAs($notificador)
            ->get(route('investigacoes.create', $evento))
            ->assertForbidden();
    }

    public function test_tela_da_investigacao_renderiza(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoEmInvestigacao();
        $investigacao = Investigacao::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $gestor->id,
        ]);

        $this->actingAs($gestor)
            ->get(route('investigacoes.show', $investigacao))
            ->assertOk()
            ->assertSee($evento->protocolo);
    }

    public function test_registra_causa_e_marca_como_raiz(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoEmInvestigacao();
        $investigacao = Investigacao::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $gestor->id,
            'metodo' => 'ishikawa',
        ]);

        $this->actingAs($gestor)->post(route('causas.store', $investigacao), [
            'categoria' => 'processo',
            'descricao' => 'Ausência de dupla checagem na dispensação.',
            'eh_causa_raiz' => '1',
        ])->assertRedirect();

        $causa = $investigacao->causas()->first();
        $this->assertNotNull($causa);
        $this->assertTrue($causa->eh_causa_raiz);
        $this->assertSame(1, $causa->ordem);
    }

    public function test_ordem_da_causa_e_incremental_quando_nao_informada(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoEmInvestigacao();
        $investigacao = Investigacao::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $gestor->id,
        ]);

        $this->actingAs($gestor)->post(route('causas.store', $investigacao), ['categoria' => 'pessoa', 'descricao' => 'Causa 1']);
        $this->actingAs($gestor)->post(route('causas.store', $investigacao), ['categoria' => 'pessoa', 'descricao' => 'Causa 2']);

        $this->assertSame([1, 2], $investigacao->causas()->orderBy('ordem')->pluck('ordem')->all());
    }

    public function test_conclusao_bloqueada_sem_causa_raiz(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoEmInvestigacao();
        $investigacao = Investigacao::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $gestor->id,
        ]);
        $investigacao->causas()->create(['categoria' => 'pessoa', 'descricao' => 'Causa sem marcação', 'eh_causa_raiz' => false]);

        $this->actingAs($gestor)
            ->post(route('investigacoes.concluir', $investigacao), [
                'causa_raiz' => 'Síntese',
                'conclusao' => 'Conclusão final.',
            ])
            ->assertRedirect();

        $this->assertSame(StatusInvestigacao::EmAndamento, $investigacao->fresh()->status);
    }

    public function test_conclusao_move_evento_para_aguardando_acao(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoEmInvestigacao();
        $investigacao = Investigacao::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $gestor->id,
        ]);
        $investigacao->causas()->create(['categoria' => 'pessoa', 'descricao' => 'Causa raiz identificada', 'eh_causa_raiz' => true]);

        $this->actingAs($gestor)
            ->post(route('investigacoes.concluir', $investigacao), [
                'causa_raiz' => 'Ausência de barreira de verificação.',
                'conclusao' => 'Recomenda-se padronizar o processo.',
            ])
            ->assertRedirect(route('eventos.show', $evento));

        $investigacao->refresh();
        $evento->refresh();
        $this->assertSame(StatusInvestigacao::Concluida, $investigacao->status);
        $this->assertNotNull($investigacao->data_conclusao);
        $this->assertSame(StatusEvento::AguardandoAcao, $evento->status);
    }

    public function test_investigacao_concluida_nao_aceita_novas_causas(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoEmInvestigacao();
        $investigacao = Investigacao::factory()->concluida()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $gestor->id,
        ]);

        $this->actingAs($gestor)
            ->post(route('causas.store', $investigacao), ['categoria' => 'pessoa', 'descricao' => 'Tentativa tardia'])
            ->assertForbidden();
    }

    public function test_remove_causa_da_investigacao(): void
    {
        $gestor = $this->criarGestor();
        $evento = $this->eventoEmInvestigacao();
        $investigacao = Investigacao::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $gestor->id,
        ]);
        $causa = $investigacao->causas()->create(['categoria' => 'pessoa', 'descricao' => 'Causa a remover']);

        $this->actingAs($gestor)
            ->delete(route('causas.destroy', [$investigacao, $causa]))
            ->assertRedirect();

        $this->assertSame(0, $investigacao->causas()->count());
    }
}
