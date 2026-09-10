<?php

namespace App\Http\Controllers;

use App\Models\CategoriaEvento;
use App\Models\EventoAdverso;
use App\Models\Setor;
use App\Services\IndicadorService;
use Illuminate\Http\Request;

/**
 * Painel de indicadores de segurança do paciente (T03 — UC15).
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly IndicadorService $indicadores,
    ) {
    }

    /** T03 — Painel com KPIs, filtros globais e gráficos. */
    public function index(Request $request)
    {
        $filtros = $this->filtros($request);

        $recentes = EventoAdverso::query()
            ->whereNotIn('status', ['rascunho'])
            ->with(['categoria.pai', 'setorOcorrencia'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('dashboard.index', [
            'indicadores' => $this->indicadores->calcular($filtros),
            'recentes' => $recentes,
            'setores' => Setor::ativos()->orderBy('nome')->get(),
            'categoriasRaiz' => CategoriaEvento::raizes()->ativas()->get(),
            'filtros' => $filtros,
        ]);
    }

    /** JSON — série temporal mensal (linha). */
    public function serieTemporal(Request $request)
    {
        return response()->json($this->indicadores->serieTemporal($this->filtros($request)));
    }

    /** JSON — top 10 categorias (barras horizontais). */
    public function porCategoria(Request $request)
    {
        return response()->json($this->indicadores->porCategoria($this->filtros($request)));
    }

    /** JSON — distribuição por setor (barras). */
    public function porSetor(Request $request)
    {
        return response()->json($this->indicadores->porSetor($this->filtros($request)));
    }

    /** JSON — distribuição por grau de dano (rosca). */
    public function porGrauDano(Request $request)
    {
        return response()->json($this->indicadores->porGrauDano($this->filtros($request)));
    }

    /** JSON — matriz de risco 5×5 (heatmap clicável). */
    public function matrizRisco(Request $request)
    {
        return response()->json($this->indicadores->matrizRisco($this->filtros($request)));
    }

    /** JSON — os indicadores da Seção 6.6. */
    public function indicadores(Request $request)
    {
        return response()->json($this->indicadores->calcular($this->filtros($request)));
    }

    /** Normaliza os filtros globais do painel. */
    private function filtros(Request $request): array
    {
        return [
            'inicio' => $request->input('inicio'),
            'fim' => $request->input('fim'),
            'setor_id' => $request->input('setor_id'),
            'categoria_id' => $request->input('categoria_id'),
            'grau_dano' => $request->input('grau_dano'),
        ];
    }
}
