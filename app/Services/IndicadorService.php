<?php

namespace App\Services;

use App\Enums\StatusAcao;
use App\Models\AcaoCorretiva;
use App\Models\Configuracao;
use App\Models\EventoAdverso;
use App\Models\Setor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Cálculo dos indicadores de segurança do paciente (Seção 6.6).
 * Resultados cacheados por 10 minutos com chave sensível aos filtros.
 */
class IndicadorService
{
    /**
     * Calcula todos os indicadores para os filtros informados.
     *
     * @param  array{inicio?:string|null,fim?:string|null,setor_id?:int|null,categoria_id?:int|null,grau_dano?:string|null}  $filtros
     * @return array<string, float|int|null>
     */
    public function calcular(array $filtros = []): array
    {
        $chave = 'indicadores.' . md5(json_encode($filtros));

        return Cache::remember($chave, now()->addMinutes(10), function () use ($filtros) {
            $inicio = isset($filtros['inicio']) && $filtros['inicio']
                ? Carbon::parse($filtros['inicio'])
                : now()->subMonths(12)->startOfDay();
            $fim = isset($filtros['fim']) && $filtros['fim']
                ? Carbon::parse($filtros['fim'])
                : now()->endOfDay();

            $base = $this->consultaBase($filtros, $inicio, $fim);

            return [
                'total_eventos' => (clone $base)->count(),
                'taxa_por_mil_pacientes_dia' => $this->taxaPorMilPacientesDia($base, $inicio, $fim, $filtros),
                'taxa_evitabilidade' => $this->taxaEvitabilidade($base),
                'taxa_com_dano' => $this->taxaComDano($base),
                'tempo_medio_triagem_horas' => $this->tempoMedioTriagemHoras($base),
                'tempo_medio_conclusao_dias' => $this->tempoMedioConclusaoDias($base),
                'taxa_acoes_no_prazo' => $this->taxaAcoesNoPrazo($inicio, $fim, $filtros),
                'aderencia_sla' => $this->aderenciaSla($base),
                'acoes_em_atraso' => AcaoCorretiva::where('status', StatusAcao::Atrasada)->count(),
            ];
        });
    }

    /** Consulta base de eventos não-rascunho/não-cancelados no período, com filtros. */
    private function consultaBase(array $filtros, Carbon $inicio, Carbon $fim): Builder
    {
        return EventoAdverso::query()
            ->whereNotIn('status', ['rascunho', 'cancelado'])
            ->whereBetween('data_ocorrencia', [$inicio->toDateString(), $fim->toDateString()])
            ->when($filtros['setor_id'] ?? null, fn ($q, $v) => $q->where('setor_ocorrencia_id', $v))
            ->when($filtros['categoria_id'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('categoria_evento_id', $v)
                        ->orWhereHas('categoria', fn ($c) => $c->where('categoria_pai_id', $v));
                });
            })
            ->when($filtros['grau_dano'] ?? null, fn ($q, $v) => $q->where('grau_dano', $v));
    }

    /**
     * Taxa de eventos por 1.000 pacientes-dia.
     * Pacientes-dia estimado = Σ leitos × dias do período × taxa de ocupação.
     */
    public function taxaPorMilPacientesDia(Builder $base, Carbon $inicio, Carbon $fim, array $filtros = []): ?float
    {
        $leitos = Setor::ativos()
            ->when($filtros['setor_id'] ?? null, fn ($q, $v) => $q->where('id', $v))
            ->sum('leitos');

        $dias = max(1, (int) $inicio->diffInDays($fim));
        $ocupacao = (float) Configuracao::obter('taxa_ocupacao_estimada', 0.8);
        $pacientesDia = $leitos * $dias * $ocupacao;

        if ($pacientesDia <= 0) {
            return null;
        }

        return round(((clone $base)->count() / $pacientesDia) * 1000, 2);
    }

    /** Taxa de evitabilidade = (evitável + provavelmente evitável) ÷ total classificado. */
    public function taxaEvitabilidade(Builder $base): ?float
    {
        $classificados = (clone $base)->where('evitabilidade', '!=', 'nao_avaliado')->count();

        if ($classificados === 0) {
            return null;
        }

        $evitaveis = (clone $base)
            ->whereIn('evitabilidade', ['evitavel', 'provavelmente_evitavel'])
            ->count();

        return round(($evitaveis / $classificados) * 100, 1);
    }

    /** Taxa de eventos com dano = grau_dano ≠ nenhum ÷ total. */
    public function taxaComDano(Builder $base): ?float
    {
        $total = (clone $base)->count();

        if ($total === 0) {
            return null;
        }

        $comDano = (clone $base)->where('grau_dano', '!=', 'nenhum')->count();

        return round(($comDano / $total) * 100, 1);
    }

    /** Tempo médio de triagem (horas) = média(triado_em − created_at). */
    public function tempoMedioTriagemHoras(Builder $base): ?float
    {
        $eventos = (clone $base)->whereNotNull('triado_em')->get(['created_at', 'triado_em']);

        if ($eventos->isEmpty()) {
            return null;
        }

        $media = $eventos->avg(fn ($e) => $e->created_at->diffInMinutes($e->triado_em) / 60);

        return round($media, 1);
    }

    /** Tempo médio de conclusão (dias) = média(concluido_em − created_at). */
    public function tempoMedioConclusaoDias(Builder $base): ?float
    {
        $eventos = (clone $base)->whereNotNull('concluido_em')->get(['created_at', 'concluido_em']);

        if ($eventos->isEmpty()) {
            return null;
        }

        $media = $eventos->avg(fn ($e) => $e->created_at->diffInHours($e->concluido_em) / 24);

        return round($media, 1);
    }

    /** Taxa de ações concluídas no prazo = concluídas com data_conclusao ≤ prazo ÷ concluídas. */
    public function taxaAcoesNoPrazo(Carbon $inicio, Carbon $fim, array $filtros = []): ?float
    {
        $concluidas = AcaoCorretiva::where('status', StatusAcao::Concluida)
            ->whereNotNull('data_conclusao')
            ->whereHas('evento', function ($q) use ($inicio, $fim, $filtros) {
                $q->whereBetween('data_ocorrencia', [$inicio->toDateString(), $fim->toDateString()])
                    ->when($filtros['setor_id'] ?? null, fn ($qq, $v) => $qq->where('setor_ocorrencia_id', $v));
            })
            ->get(['prazo', 'data_conclusao']);

        if ($concluidas->isEmpty()) {
            return null;
        }

        $noPrazo = $concluidas->filter(fn ($a) => $a->data_conclusao->lte($a->prazo))->count();

        return round(($noPrazo / $concluidas->count()) * 100, 1);
    }

    /** Aderência ao SLA = eventos concluídos dentro do prazo_conclusao ÷ concluídos. */
    public function aderenciaSla(Builder $base): ?float
    {
        $concluidos = (clone $base)
            ->whereNotNull('concluido_em')
            ->whereNotNull('prazo_conclusao')
            ->get(['concluido_em', 'prazo_conclusao']);

        if ($concluidos->isEmpty()) {
            return null;
        }

        $dentroPrazo = $concluidos
            ->filter(fn ($e) => $e->concluido_em->startOfDay()->lte($e->prazo_conclusao))
            ->count();

        return round(($dentroPrazo / $concluidos->count()) * 100, 1);
    }

    // ── Séries para os gráficos do dashboard ───────────────────────────

    /** Série temporal mensal de eventos (linha). */
    public function serieTemporal(array $filtros = []): array
    {
        $inicio = isset($filtros['inicio']) && $filtros['inicio']
            ? Carbon::parse($filtros['inicio']) : now()->subMonths(12)->startOfMonth();
        $fim = isset($filtros['fim']) && $filtros['fim']
            ? Carbon::parse($filtros['fim']) : now();

        $eventos = $this->consultaBase($filtros, $inicio, $fim)
            ->selectRaw("DATE_FORMAT(data_ocorrencia, '%Y-%m') as mes, COUNT(*) as total")
            ->groupBy('mes')->orderBy('mes')->pluck('total', 'mes');

        $rotulos = [];
        $valores = [];
        $cursor = $inicio->copy()->startOfMonth();

        while ($cursor->lte($fim)) {
            $chave = $cursor->format('Y-m');
            $rotulos[] = $cursor->translatedFormat('M/y');
            $valores[] = (int) ($eventos[$chave] ?? 0);
            $cursor->addMonth();
        }

        return ['rotulos' => $rotulos, 'valores' => $valores];
    }

    /** Top 10 categorias-raiz por volume (barras horizontais). */
    public function porCategoria(array $filtros = []): array
    {
        [$inicio, $fim] = $this->periodo($filtros);

        $dados = $this->consultaBase($filtros, $inicio, $fim)
            ->join('categorias_evento as c', 'c.id', '=', 'eventos_adversos.categoria_evento_id')
            ->leftJoin('categorias_evento as raiz', 'raiz.id', '=', 'c.categoria_pai_id')
            ->selectRaw('COALESCE(raiz.nome, c.nome) as categoria, COUNT(*) as total')
            ->groupBy('categoria')->orderByDesc('total')->limit(10)
            ->get();

        return [
            'rotulos' => $dados->pluck('categoria')->all(),
            'valores' => $dados->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /** Distribuição por setor de ocorrência (barras). */
    public function porSetor(array $filtros = []): array
    {
        [$inicio, $fim] = $this->periodo($filtros);

        $dados = $this->consultaBase($filtros, $inicio, $fim)
            ->join('setores', 'setores.id', '=', 'eventos_adversos.setor_ocorrencia_id')
            ->selectRaw('setores.sigla as setor, COUNT(*) as total')
            ->groupBy('setor')->orderByDesc('total')
            ->get();

        return [
            'rotulos' => $dados->pluck('setor')->all(),
            'valores' => $dados->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /** Distribuição por grau de dano (rosca). */
    public function porGrauDano(array $filtros = []): array
    {
        [$inicio, $fim] = $this->periodo($filtros);

        $ordem = ['nenhum', 'leve', 'moderado', 'grave', 'obito'];
        $rotulosMap = [
            'nenhum' => 'Nenhum', 'leve' => 'Leve', 'moderado' => 'Moderado',
            'grave' => 'Grave', 'obito' => 'Óbito',
        ];

        $dados = $this->consultaBase($filtros, $inicio, $fim)
            ->selectRaw('grau_dano, COUNT(*) as total')
            ->groupBy('grau_dano')->pluck('total', 'grau_dano');

        return [
            'rotulos' => array_map(fn ($g) => $rotulosMap[$g], $ordem),
            'valores' => array_map(fn ($g) => (int) ($dados[$g] ?? 0), $ordem),
        ];
    }

    /** Matriz de risco 5×5: contagem por célula probabilidade × severidade. */
    public function matrizRisco(array $filtros = []): array
    {
        [$inicio, $fim] = $this->periodo($filtros);

        $dados = $this->consultaBase($filtros, $inicio, $fim)
            ->whereNotNull('probabilidade')->whereNotNull('severidade')
            ->selectRaw('probabilidade, severidade, COUNT(*) as total')
            ->groupBy('probabilidade', 'severidade')
            ->get();

        $matriz = [];
        foreach (range(1, 5) as $p) {
            foreach (range(1, 5) as $s) {
                $matriz[$p][$s] = 0;
            }
        }

        foreach ($dados as $linha) {
            $matriz[$linha->probabilidade][$linha->severidade] = (int) $linha->total;
        }

        return $matriz;
    }

    /** Resolve o período dos filtros com padrão de 12 meses. */
    private function periodo(array $filtros): array
    {
        return [
            isset($filtros['inicio']) && $filtros['inicio']
                ? Carbon::parse($filtros['inicio']) : now()->subMonths(12)->startOfDay(),
            isset($filtros['fim']) && $filtros['fim']
                ? Carbon::parse($filtros['fim']) : now()->endOfDay(),
        ];
    }
}
