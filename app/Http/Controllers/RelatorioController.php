<?php

namespace App\Http\Controllers;

use App\Enums\StatusAcao;
use App\Exports\EventosExport;
use App\Models\AcaoCorretiva;
use App\Models\CategoriaEvento;
use App\Models\Configuracao;
use App\Models\EventoAdverso;
use App\Models\Setor;
use App\Services\IndicadorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Relatórios gerenciais com exportação em PDF e Excel (T16 — UC16/UC17).
 * Atende ao objetivo específico (f) do TCC.
 */
class RelatorioController extends Controller
{
    public function __construct(
        private readonly IndicadorService $indicadores,
    ) {
    }

    /** T16 — Menu de relatórios. */
    public function index()
    {
        return view('relatorios.index', [
            'setores' => Setor::ativos()->orderBy('nome')->get(),
        ]);
    }

    /** Relatório consolidado: indicadores + distribuições do período. */
    public function consolidado(Request $request)
    {
        return view('relatorios.consolidado', $this->dadosConsolidado($request));
    }

    /** Relatório por setor. */
    public function porSetor(Request $request)
    {
        return view('relatorios.setor', $this->dadosPorSetor($request));
    }

    /** Relatório por categoria. */
    public function porCategoria(Request $request)
    {
        return view('relatorios.categoria', $this->dadosPorCategoria($request));
    }

    /** Relatório de ações pendentes/atrasadas. */
    public function acoesPendentes(Request $request)
    {
        return view('relatorios.acoes', $this->dadosAcoes($request));
    }

    /** Exportação em PDF de qualquer relatório. */
    public function pdf(Request $request, string $tipo)
    {
        [$view, $dados] = $this->resolver($request, $tipo);

        return Pdf::loadView("relatorios.pdf.{$view}", $dados + ['gerado_em' => now()])
            ->setPaper('a4', $tipo === 'consolidado' ? 'portrait' : 'landscape')
            ->download("relatorio_{$tipo}_" . now()->format('Y-m-d') . '.pdf');
    }

    /** Exportação em Excel (base de eventos filtrada). */
    public function excel(Request $request, string $tipo)
    {
        return Excel::download(
            new EventosExport($request->all()),
            "relatorio_{$tipo}_" . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /** Resolve view e dados por tipo de relatório. */
    private function resolver(Request $request, string $tipo): array
    {
        return match ($tipo) {
            'consolidado' => ['consolidado', $this->dadosConsolidado($request)],
            'por-setor' => ['setor', $this->dadosPorSetor($request)],
            'por-categoria' => ['categoria', $this->dadosPorCategoria($request)],
            'acoes-pendentes' => ['acoes', $this->dadosAcoes($request)],
            default => abort(404),
        };
    }

    private function dadosConsolidado(Request $request): array
    {
        $filtros = $this->filtros($request);

        return [
            'filtros' => $filtros,
            'instituicao' => Configuracao::obter('nome_instituicao', 'Instituição de Saúde'),
            'indicadores' => $this->indicadores->calcular($filtros),
            'serieTemporal' => $this->indicadores->serieTemporal($filtros),
            'porCategoria' => $this->indicadores->porCategoria($filtros),
            'porSetor' => $this->indicadores->porSetor($filtros),
            'porGrauDano' => $this->indicadores->porGrauDano($filtros),
        ];
    }

    private function dadosPorSetor(Request $request): array
    {
        $filtros = $this->filtros($request);

        $linhas = Setor::ativos()
            ->withCount([
                'eventos as total' => fn ($q) => $this->aplicarPeriodo($q, $filtros),
                'eventos as com_dano' => fn ($q) => $this->aplicarPeriodo($q, $filtros)->where('grau_dano', '!=', 'nenhum'),
                'eventos as graves' => fn ($q) => $this->aplicarPeriodo($q, $filtros)->whereIn('grau_dano', ['grave', 'obito']),
                'eventos as concluidos' => fn ($q) => $this->aplicarPeriodo($q, $filtros)->where('status', 'concluido'),
            ])
            ->orderByDesc('total')
            ->get();

        return [
            'filtros' => $filtros,
            'instituicao' => Configuracao::obter('nome_instituicao', 'Instituição de Saúde'),
            'linhas' => $linhas,
        ];
    }

    private function dadosPorCategoria(Request $request): array
    {
        $filtros = $this->filtros($request);

        $linhas = CategoriaEvento::raizes()
            ->get()
            ->map(function (CategoriaEvento $raiz) use ($filtros) {
                $base = EventoAdverso::query()
                    ->whereNotIn('status', ['rascunho', 'cancelado'])
                    ->where(function ($q) use ($raiz) {
                        $q->where('categoria_evento_id', $raiz->id)
                            ->orWhereHas('categoria', fn ($c) => $c->where('categoria_pai_id', $raiz->id));
                    });
                $this->aplicarPeriodo($base, $filtros);

                $raiz->total = (clone $base)->count();
                $raiz->com_dano = (clone $base)->where('grau_dano', '!=', 'nenhum')->count();
                $raiz->evitaveis = (clone $base)->whereIn('evitabilidade', ['evitavel', 'provavelmente_evitavel'])->count();

                return $raiz;
            })
            ->sortByDesc('total')
            ->values();

        return [
            'filtros' => $filtros,
            'instituicao' => Configuracao::obter('nome_instituicao', 'Instituição de Saúde'),
            'linhas' => $linhas,
        ];
    }

    private function dadosAcoes(Request $request): array
    {
        $filtros = $this->filtros($request);

        $acoes = AcaoCorretiva::query()
            ->whereIn('status', [StatusAcao::Pendente, StatusAcao::EmAndamento, StatusAcao::Atrasada])
            ->with(['evento', 'responsavel', 'setor'])
            ->when($filtros['setor_id'] ?? null, fn ($q, $v) => $q->where('setor_id', $v))
            ->orderBy('prazo')
            ->get();

        return [
            'filtros' => $filtros,
            'instituicao' => Configuracao::obter('nome_instituicao', 'Instituição de Saúde'),
            'acoes' => $acoes,
        ];
    }

    private function aplicarPeriodo($query, array $filtros)
    {
        return $query
            ->whereNotIn('status', ['rascunho', 'cancelado'])
            ->when($filtros['inicio'] ?? null, fn ($q, $v) => $q->whereDate('data_ocorrencia', '>=', $v))
            ->when($filtros['fim'] ?? null, fn ($q, $v) => $q->whereDate('data_ocorrencia', '<=', $v));
    }

    private function filtros(Request $request): array
    {
        return [
            'inicio' => $request->input('inicio', now()->subMonths(12)->toDateString()),
            'fim' => $request->input('fim', now()->toDateString()),
            'setor_id' => $request->input('setor_id'),
        ];
    }
}
