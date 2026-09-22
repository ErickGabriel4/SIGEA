<?php

namespace Tests\Unit;

use App\Models\EventoAdverso;
use App\Services\ProtocoloService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Geração de protocolo: formato, sequência e virada de ano (Seção 6.3).
 */
class ProtocoloServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProtocoloService $servico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servico = new ProtocoloService();
    }

    public function test_formato_ea_ano_sequencial_de_seis_digitos(): void
    {
        $this->assertSame('EA-2026-000001', $this->servico->formatar(2026, 1));
        $this->assertSame('EA-2026-004321', $this->servico->formatar(2026, 4321));
    }

    public function test_primeiro_protocolo_do_ano_comeca_em_um(): void
    {
        $this->assertSame('EA-' . now()->year . '-000001', $this->servico->gerar());
    }

    public function test_sequencia_incrementa_a_partir_do_ultimo(): void
    {
        $this->semearPerfis();
        [$setor, $categoria] = $this->criarBaseEvento();

        EventoAdverso::factory()->create([
            'notificante_id' => $this->criarNotificador()->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);

        $this->assertSame('EA-' . now()->year . '-000002', $this->servico->gerar());
    }

    public function test_sequencia_reinicia_na_virada_de_ano(): void
    {
        $this->semearPerfis();
        [$setor, $categoria] = $this->criarBaseEvento();

        // Evento do ano anterior com sequencial alto
        $anoAnterior = now()->year - 1;
        $evento = EventoAdverso::factory()->make([
            'notificante_id' => $this->criarNotificador()->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);
        $evento->protocolo = "EA-{$anoAnterior}-000458";
        $evento->save();

        // O ano corrente reinicia em 000001; o ano anterior continuaria em 000459
        $this->assertSame('EA-' . now()->year . '-000001', $this->servico->gerar());
        $this->assertSame("EA-{$anoAnterior}-000459", $this->servico->gerar($anoAnterior));
    }

    public function test_geracao_em_massa_nao_produz_colisao(): void
    {
        $this->semearPerfis();
        [$setor, $categoria] = $this->criarBaseEvento();
        $notificador = $this->criarNotificador();

        // Criação em sequência via Observer (transação com lockForUpdate)
        EventoAdverso::factory()->count(30)->create([
            'notificante_id' => $notificador->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);

        $protocolos = EventoAdverso::pluck('protocolo');
        $this->assertCount(30, $protocolos->unique());
    }
}
