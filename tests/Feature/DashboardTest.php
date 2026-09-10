<?php

namespace Tests\Feature;

use App\Models\EventoAdverso;
use App\Models\Setor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard: estrutura dos endpoints JSON e respeito aos filtros (UC15).
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
    }

    private function semearEventos(): array
    {
        [$setorA, $categoria] = $this->criarBaseEvento();
        $setorB = Setor::factory()->create(['leitos' => 10]);
        $notificador = $this->criarNotificador();

        EventoAdverso::factory()->count(3)->create([
            'notificante_id' => $notificador->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setorA->id,
            'data_ocorrencia' => today()->subDays(10)->toDateString(),
        ]);
        EventoAdverso::factory()->count(2)->create([
            'notificante_id' => $notificador->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setorB->id,
            'data_ocorrencia' => today()->subDays(5)->toDateString(),
            'grau_dano' => 'moderado',
            'probabilidade' => 4,
            'severidade' => 4,
        ]);

        return [$setorA, $setorB];
    }

    public function test_painel_carrega_para_gestor(): void
    {
        $this->semearEventos();

        $this->actingAs($this->criarGestor())
            ->get('/painel')
            ->assertOk()
            ->assertSee('Painel de Segurança do Paciente');
    }

    public function test_endpoint_serie_temporal_retorna_estrutura_esperada(): void
    {
        $this->semearEventos();

        $this->actingAs($this->criarGestor())
            ->getJson(route('painel.serie'))
            ->assertOk()
            ->assertJsonStructure(['rotulos', 'valores']);
    }

    public function test_endpoint_grau_dano_retorna_cinco_categorias(): void
    {
        $this->semearEventos();

        $resposta = $this->actingAs($this->criarGestor())
            ->getJson(route('painel.dano'))
            ->assertOk()
            ->json();

        $this->assertCount(5, $resposta['rotulos']);
        $this->assertSame(2, $resposta['valores'][2]); // moderado
    }

    public function test_endpoint_matriz_risco_retorna_grade_5x5(): void
    {
        $this->semearEventos();

        $matriz = $this->actingAs($this->criarGestor())
            ->getJson(route('painel.matriz'))
            ->assertOk()
            ->json();

        $this->assertCount(5, $matriz);
        $this->assertCount(5, $matriz['1']);
        $this->assertSame(2, $matriz['4']['4']);
    }

    public function test_filtro_de_setor_afeta_o_resultado(): void
    {
        [$setorA, $setorB] = $this->semearEventos();

        $gestor = $this->criarGestor();

        $todos = $this->actingAs($gestor)->getJson(route('painel.indicadores'))->json();
        $filtrado = $this->actingAs($gestor)
            ->getJson(route('painel.indicadores', ['setor_id' => $setorB->id]))
            ->json();

        $this->assertSame(5, $todos['total_eventos']);
        $this->assertSame(2, $filtrado['total_eventos']);
    }

    public function test_endpoints_exigem_permissao_de_dashboard(): void
    {
        $this->actingAs($this->criarNotificador())
            ->getJson(route('painel.indicadores'))
            ->assertForbidden();
    }
}
