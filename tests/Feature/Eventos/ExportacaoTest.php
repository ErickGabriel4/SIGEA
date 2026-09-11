<?php

namespace Tests\Feature\Eventos;

use App\Models\EventoAdverso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ficha do evento em PDF e exportação da listagem em CSV/XLSX (RF11/US29).
 */
class ExportacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
    }

    public function test_ficha_do_evento_em_pdf_e_gerada(): void
    {
        [$setor, $categoria] = $this->criarBaseEvento();
        $gestor = $this->criarGestor();

        $evento = EventoAdverso::factory()->create([
            'notificante_id' => $this->criarNotificador()->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);

        $resposta = $this->actingAs($gestor)->get(route('eventos.pdf', $evento));

        $resposta->assertOk();
        $this->assertSame('application/pdf', $resposta->headers->get('content-type'));
    }

    public function test_ficha_em_pdf_respeita_a_policy_de_visualizacao(): void
    {
        [$setor, $categoria] = $this->criarBaseEvento();

        $evento = EventoAdverso::factory()->create([
            'notificante_id' => $this->criarNotificador()->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);

        $outroNotificador = $this->criarNotificador();

        $this->actingAs($outroNotificador)
            ->get(route('eventos.pdf', $evento))
            ->assertForbidden();
    }

    public function test_exportacao_excel_e_gerada(): void
    {
        [$setor, $categoria] = $this->criarBaseEvento();
        $gestor = $this->criarGestor();

        EventoAdverso::factory()->create([
            'notificante_id' => $this->criarNotificador()->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);

        $this->actingAs($gestor)
            ->get(route('eventos.exportar'))
            ->assertOk()
            ->assertDownload();
    }

    public function test_exportacao_exige_permissao_evento_exportar(): void
    {
        $this->actingAs($this->criarNotificador())
            ->get(route('eventos.exportar'))
            ->assertForbidden();
    }
}
