<?php

namespace Tests\Unit;

use App\Enums\ClassificacaoIncidente;
use App\Enums\GrauDano;
use App\Enums\NivelRisco;
use App\Enums\Prioridade;
use App\Models\EventoAdverso;
use App\Services\MatrizRiscoService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Matriz de risco 5×5 — tabela completa de faixas (Quadro A.13).
 */
class MatrizRiscoServiceTest extends TestCase
{
    private MatrizRiscoService $servico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servico = new MatrizRiscoService();
    }

    public static function faixasDeScore(): array
    {
        return [
            'score 1 → baixo' => [1, NivelRisco::Baixo],
            'score 4 → baixo' => [4, NivelRisco::Baixo],
            'score 5 → moderado' => [5, NivelRisco::Moderado],
            'score 9 → moderado' => [9, NivelRisco::Moderado],
            'score 10 → alto' => [10, NivelRisco::Alto],
            'score 15 → alto' => [15, NivelRisco::Alto],
            'score 16 → extremo' => [16, NivelRisco::Extremo],
            'score 25 → extremo' => [25, NivelRisco::Extremo],
        ];
    }

    #[DataProvider('faixasDeScore')]
    public function test_nivel_por_score(int $score, NivelRisco $esperado): void
    {
        $this->assertSame($esperado, $this->servico->nivelPorScore($score));
    }

    public function test_score_e_o_produto_de_probabilidade_por_severidade(): void
    {
        $this->assertSame(12, $this->servico->calcularScore(3, 4));
        $this->assertSame(1, $this->servico->calcularScore(1, 1));
        $this->assertSame(25, $this->servico->calcularScore(5, 5));
    }

    public function test_prioridade_sugerida_por_nivel(): void
    {
        $this->assertSame(Prioridade::Baixa, $this->servico->prioridadeSugerida(NivelRisco::Baixo));
        $this->assertSame(Prioridade::Media, $this->servico->prioridadeSugerida(NivelRisco::Moderado));
        $this->assertSame(Prioridade::Alta, $this->servico->prioridadeSugerida(NivelRisco::Alto));
        $this->assertSame(Prioridade::Critica, $this->servico->prioridadeSugerida(NivelRisco::Extremo));
    }

    public function test_sla_em_dias_por_nivel(): void
    {
        $this->assertSame(60, $this->servico->slaDias(NivelRisco::Baixo));
        $this->assertSame(45, $this->servico->slaDias(NivelRisco::Moderado));
        $this->assertSame(30, $this->servico->slaDias(NivelRisco::Alto));
        $this->assertSame(15, $this->servico->slaDias(NivelRisco::Extremo));
    }

    public function test_prazo_de_conclusao_soma_o_sla_a_data_de_notificacao(): void
    {
        $base = now()->parse('2026-03-01');

        $this->assertSame('2026-03-16', $this->servico->prazoConclusao(NivelRisco::Extremo, $base)->toDateString());
        $this->assertSame('2026-04-30', $this->servico->prazoConclusao(NivelRisco::Baixo, $base)->toDateString());
    }

    public function test_dano_grave_forca_prioridade_critica(): void
    {
        $evento = new EventoAdverso();
        $evento->grau_dano = GrauDano::Grave;
        $evento->classificacao = ClassificacaoIncidente::EventoAdverso;

        $this->assertSame(Prioridade::Critica, $this->servico->prioridadeCorrigida($evento, NivelRisco::Baixo));
    }

    public function test_never_event_forca_prioridade_critica(): void
    {
        $evento = new EventoAdverso();
        $evento->grau_dano = GrauDano::Nenhum;
        $evento->classificacao = ClassificacaoIncidente::NeverEvent;

        $this->assertSame(Prioridade::Critica, $this->servico->prioridadeCorrigida($evento, NivelRisco::Moderado));
    }
}
