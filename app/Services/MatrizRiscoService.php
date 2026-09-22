<?php

namespace App\Services;

use App\Enums\ClassificacaoIncidente;
use App\Enums\GrauDano;
use App\Enums\NivelRisco;
use App\Enums\Prioridade;
use App\Models\EventoAdverso;
use Carbon\Carbon;

/**
 * Matriz de risco 5×5: score = probabilidade × severidade (Seção 6.2 / Quadro A.13).
 */
class MatrizRiscoService
{
    /** SLA de conclusão (dias corridos) por nível de risco. */
    public const SLA_DIAS = [
        'baixo' => 60,
        'moderado' => 45,
        'alto' => 30,
        'extremo' => 15,
    ];

    /** Calcula o score (1–25) a partir de probabilidade e severidade (1–5). */
    public function calcularScore(int $probabilidade, int $severidade): int
    {
        return $probabilidade * $severidade;
    }

    /** Deriva o nível de risco a partir do score (faixas do Quadro A.13). */
    public function nivelPorScore(int $score): NivelRisco
    {
        return match (true) {
            $score <= 4 => NivelRisco::Baixo,
            $score <= 9 => NivelRisco::Moderado,
            $score <= 15 => NivelRisco::Alto,
            default => NivelRisco::Extremo,
        };
    }

    /** Prioridade sugerida para cada nível de risco. */
    public function prioridadeSugerida(NivelRisco $nivel): Prioridade
    {
        return match ($nivel) {
            NivelRisco::Baixo => Prioridade::Baixa,
            NivelRisco::Moderado => Prioridade::Media,
            NivelRisco::Alto => Prioridade::Alta,
            NivelRisco::Extremo => Prioridade::Critica,
        };
    }

    /** SLA (dias) para o nível de risco. */
    public function slaDias(NivelRisco $nivel): int
    {
        return self::SLA_DIAS[$nivel->value];
    }

    /** Prazo de conclusão = data de notificação + SLA do nível. */
    public function prazoConclusao(NivelRisco $nivel, ?Carbon $dataNotificacao = null): Carbon
    {
        $base = $dataNotificacao ?? now();

        return $base->copy()->addDays($this->slaDias($nivel))->startOfDay();
    }

    /**
     * Aplica a matriz ao evento: nível de risco, prioridade e prazo.
     * Grau de dano grave/óbito ou never event ⇒ prioridade crítica sempre.
     */
    public function aplicar(EventoAdverso $evento): EventoAdverso
    {
        if ($evento->probabilidade === null || $evento->severidade === null) {
            return $evento;
        }

        $score = $this->calcularScore($evento->probabilidade, $evento->severidade);
        $nivel = $this->nivelPorScore($score);

        $evento->nivel_risco = $nivel;
        $evento->prioridade = $this->prioridadeCorrigida($evento, $nivel);

        if ($evento->prazo_conclusao === null) {
            $evento->prazo_conclusao = $this->prazoConclusao($nivel, $evento->created_at);
        }

        return $evento;
    }

    /** Prioridade final considerando as regras de exceção (Seção 6.2). */
    public function prioridadeCorrigida(EventoAdverso $evento, NivelRisco $nivel): Prioridade
    {
        $danoCritico = $evento->grau_dano instanceof GrauDano && $evento->grau_dano->ehGraveOuObito();
        $neverEvent = $evento->classificacao === ClassificacaoIncidente::NeverEvent;

        if ($danoCritico || $neverEvent) {
            return Prioridade::Critica;
        }

        return $this->prioridadeSugerida($nivel);
    }
}
