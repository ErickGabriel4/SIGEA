<?php

namespace Tests\Unit;

use App\Models\AcaoCorretiva;
use App\Models\Configuracao;
use App\Models\EventoAdverso;
use App\Models\Setor;
use App\Services\IndicadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fórmulas dos indicadores da Seção 6.6, com dados controlados.
 */
class IndicadorServiceTest extends TestCase
{
    use RefreshDatabase;

    private IndicadorService $servico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
        $this->servico = app(IndicadorService::class);
    }

    /** Base controlada: 4 eventos no período (2 com dano, 2 evitáveis, 2 concluídos). */
    private function semearCenario(): void
    {
        [$setor, $categoria] = $this->criarBaseEvento();
        $notificador = $this->criarNotificador();

        Configuracao::create([
            'chave' => 'taxa_ocupacao_estimada', 'valor' => '0.8',
            'tipo' => 'string', 'grupo' => 'indicadores',
        ]);

        $base = [
            'notificante_id' => $notificador->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
            'data_ocorrencia' => today()->subDays(15)->toDateString(),
        ];

        // 1: sem dano, evitável, concluído em 2 dias (triagem em 4h), dentro do prazo
        $evento = EventoAdverso::factory()->create($base + [
            'grau_dano' => 'nenhum', 'evitabilidade' => 'evitavel', 'status' => 'concluido',
            'prazo_conclusao' => today()->addDays(10)->toDateString(),
        ]);
        $evento->forceFill([
            'triado_em' => $evento->created_at->copy()->addHours(4),
            'concluido_em' => $evento->created_at->copy()->addDays(2),
        ])->saveQuietly();

        // 2: dano moderado, provavelmente evitável, concluído em 4 dias (triagem em 8h), fora do prazo
        $evento = EventoAdverso::factory()->create($base + [
            'grau_dano' => 'moderado', 'evitabilidade' => 'provavelmente_evitavel', 'status' => 'concluido',
            'prazo_conclusao' => $evento->created_at->copy()->addDay()->toDateString(),
        ]);
        $evento->forceFill([
            'triado_em' => $evento->created_at->copy()->addHours(8),
            'concluido_em' => $evento->created_at->copy()->addDays(4),
        ])->saveQuietly();

        // 3: dano leve, inevitável, em investigação
        EventoAdverso::factory()->create($base + [
            'grau_dano' => 'leve', 'evitabilidade' => 'inevitavel', 'status' => 'em_investigacao',
        ]);

        // 4: sem dano, não avaliado, notificado
        EventoAdverso::factory()->create($base + [
            'grau_dano' => 'nenhum', 'evitabilidade' => 'nao_avaliado', 'status' => 'notificado',
        ]);

        // Rascunho e cancelado NÃO entram na base
        EventoAdverso::factory()->rascunho()->create($base);
        EventoAdverso::factory()->create($base + ['status' => 'cancelado']);
    }

    public function test_total_ignora_rascunhos_e_cancelados(): void
    {
        $this->semearCenario();

        $indicadores = $this->servico->calcular();

        $this->assertSame(4, $indicadores['total_eventos']);
    }

    public function test_taxa_de_evitabilidade_considera_apenas_avaliados(): void
    {
        $this->semearCenario();

        // 3 avaliados (evitável, provavelmente evitável, inevitável) → 2/3 = 66,7%
        $this->assertSame(66.7, $this->servico->calcular()['taxa_evitabilidade']);
    }

    public function test_taxa_de_eventos_com_dano(): void
    {
        $this->semearCenario();

        // 2 com dano de 4 → 50%
        $this->assertSame(50.0, $this->servico->calcular()['taxa_com_dano']);
    }

    public function test_tempo_medio_de_triagem_em_horas(): void
    {
        $this->semearCenario();

        // (4 + 8) / 2 = 6 horas
        $this->assertSame(6.0, $this->servico->calcular()['tempo_medio_triagem_horas']);
    }

    public function test_tempo_medio_de_conclusao_em_dias(): void
    {
        $this->semearCenario();

        // (2 + 4) / 2 = 3 dias
        $this->assertSame(3.0, $this->servico->calcular()['tempo_medio_conclusao_dias']);
    }

    public function test_aderencia_ao_sla(): void
    {
        $this->semearCenario();

        // 2 concluídos, 1 dentro do prazo → 50%
        $this->assertSame(50.0, $this->servico->calcular()['aderencia_sla']);
    }

    public function test_taxa_de_acoes_no_prazo(): void
    {
        $this->semearCenario();
        $responsavel = $this->criarNotificador();
        $evento = EventoAdverso::first();

        AcaoCorretiva::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $responsavel->id,
            'status' => 'concluida',
            'prazo' => today()->addDays(5)->toDateString(),
            'data_conclusao' => today()->toDateString(),
        ]);
        AcaoCorretiva::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $responsavel->id,
            'status' => 'concluida',
            'prazo' => today()->subDays(5)->toDateString(),
            'data_conclusao' => today()->toDateString(),
        ]);

        // 1 de 2 no prazo → 50%
        $this->assertSame(50.0, $this->servico->calcular()['taxa_acoes_no_prazo']);
    }

    public function test_taxa_por_mil_pacientes_dia_usa_leitos_e_ocupacao(): void
    {
        $this->semearCenario();

        $filtros = [
            'inicio' => today()->subDays(30)->toDateString(),
            'fim' => today()->toDateString(),
        ];

        $leitos = Setor::ativos()->sum('leitos');
        $pacientesDia = $leitos * 30 * 0.8;
        $esperado = round((4 / $pacientesDia) * 1000, 2);

        $this->assertSame($esperado, $this->servico->calcular($filtros)['taxa_por_mil_pacientes_dia']);
    }
}
