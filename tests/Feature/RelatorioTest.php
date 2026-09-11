<?php

namespace Tests\Feature;

use App\Models\Configuracao;
use App\Models\EventoAdverso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Relatórios gerenciais e exportação em PDF/Excel (RF11/UC16/UC17).
 */
class RelatorioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();

        Configuracao::create([
            'chave' => 'nome_instituicao', 'valor' => 'Hospital Teste',
            'tipo' => 'string', 'grupo' => 'instituicao',
        ]);
    }

    private function semearEventos(): void
    {
        [$setor, $categoria] = $this->criarBaseEvento();

        EventoAdverso::factory()->count(3)->create([
            'notificante_id' => $this->criarNotificador()->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);
    }

    public function test_telas_de_relatorio_carregam_para_o_gestor(): void
    {
        $this->semearEventos();
        $gestor = $this->criarGestor();

        foreach ([
            'relatorios.index', 'relatorios.consolidado', 'relatorios.setor',
            'relatorios.categoria', 'relatorios.acoes',
        ] as $rota) {
            $this->actingAs($gestor)->get(route($rota))->assertOk();
        }
    }

    public function test_relatorio_consolidado_em_pdf_e_gerado(): void
    {
        $this->semearEventos();

        $resposta = $this->actingAs($this->criarGestor())
            ->get(route('relatorios.pdf', ['tipo' => 'consolidado']));

        $resposta->assertOk();
        $this->assertSame('application/pdf', $resposta->headers->get('content-type'));
    }

    public function test_relatorio_e_exportavel_em_excel(): void
    {
        $this->semearEventos();

        $this->actingAs($this->criarGestor())
            ->get(route('relatorios.excel', ['tipo' => 'consolidado']))
            ->assertOk()
            ->assertDownload();
    }

    public function test_relatorios_exigem_permissao_relatorio_ver(): void
    {
        $this->actingAs($this->criarNotificador())
            ->get(route('relatorios.index'))
            ->assertForbidden();
    }
}
