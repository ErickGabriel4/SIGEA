<?php

namespace Tests\Feature\Eventos;

use App\Enums\StatusEvento;
use App\Models\CategoriaEvento;
use App\Models\EventoAdverso;
use App\Models\EventoHistorico;
use App\Services\ProtocoloService;
use App\Services\PseudonimizadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modelagem do evento adverso: protocolo, pseudonimização e trilha imutável.
 */
class ModelagemEventoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
    }

    public function test_protocolo_e_gerado_no_padrao_do_ano_e_sequencial(): void
    {
        $primeiro = EventoAdverso::factory()->create();
        $segundo = EventoAdverso::factory()->create();

        $ano = now()->year;

        $this->assertSame("EA-{$ano}-000001", $primeiro->protocolo);
        $this->assertSame("EA-{$ano}-000002", $segundo->protocolo);
    }

    public function test_sequencial_do_protocolo_reinicia_a_cada_ano(): void
    {
        $servico = app(ProtocoloService::class);

        EventoAdverso::factory()->create(['protocolo' => $servico->formatar(2025, 47)]);

        $this->assertSame('EA-2025-000048', $servico->gerar(2025));
        $this->assertSame('EA-2026-000001', $servico->gerar(2026));
    }

    public function test_notificacao_anonima_nao_guarda_vinculo_com_o_notificante(): void
    {
        $notificador = $this->criarNotificador();

        $evento = EventoAdverso::factory()->create([
            'anonima' => true,
            'notificante_id' => $notificador->id,
        ]);

        $this->assertNull($evento->fresh()->notificante_id);
        $this->assertNull($evento->historico()->first()->user_id);
    }

    public function test_criacao_do_evento_abre_a_trilha_de_historico(): void
    {
        $evento = EventoAdverso::factory()->rascunho()->create();

        $historico = $evento->historico()->first();

        $this->assertNotNull($historico);
        $this->assertNull($historico->status_anterior);
        $this->assertSame(StatusEvento::Rascunho->value, $historico->status_novo);
        $this->assertSame('criacao', $historico->acao);
    }

    public function test_historico_nao_pode_ser_alterado_nem_excluido(): void
    {
        $evento = EventoAdverso::factory()->create();
        $historico = $evento->historico()->first();

        $historico->acao = 'adulteracao';
        $historico->save();
        $historico->delete();

        $this->assertDatabaseHas('evento_historico', ['id' => $historico->id, 'acao' => 'criacao']);
        $this->assertSame(1, EventoHistorico::whereKey($historico->id)->count());
    }

    public function test_identificador_do_paciente_e_pseudonimizado_de_forma_deterministica(): void
    {
        $pseudonimizador = app(PseudonimizadorService::class);

        $codigo = $pseudonimizador->codificar('prontuario 884213');

        $this->assertStringStartsWith('PAC-', $codigo);
        $this->assertSame(20, strlen($codigo));
        $this->assertStringNotContainsString('884213', $codigo);
        $this->assertSame($codigo, $pseudonimizador->codificar('prontuario 884213'));
        $this->assertSame($codigo, $pseudonimizador->codificar($codigo));
        $this->assertNull($pseudonimizador->codificar('   '));
    }

    public function test_score_de_risco_e_calculado_pelo_banco(): void
    {
        $evento = EventoAdverso::factory()->create(['probabilidade' => 4, 'severidade' => 5]);

        $this->assertSame(20, $evento->fresh()->score_risco);
    }

    public function test_categoria_em_uso_nao_pode_ser_excluida(): void
    {
        $evento = EventoAdverso::factory()->create();
        $categoria = CategoriaEvento::find($evento->categoria_evento_id);

        $this->actingAs($this->criarAdministrador())
            ->delete(route('admin.categorias.destroy', $categoria))
            ->assertSessionHas('erro');

        $this->assertNotSoftDeleted('categorias_evento', ['id' => $categoria->id]);
    }
}
