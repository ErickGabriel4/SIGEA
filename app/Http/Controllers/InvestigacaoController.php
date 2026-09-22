<?php

namespace App\Http\Controllers;

use App\Enums\StatusEvento;
use App\Enums\StatusInvestigacao;
use App\Exceptions\TransicaoInvalidaException;
use App\Http\Requests\InvestigacaoRequest;
use App\Models\EventoAdverso;
use App\Models\Investigacao;
use App\Models\User;
use App\Services\WorkflowEventoService;
use Illuminate\Http\Request;

/**
 * Investigação e análise de causa raiz (T11/T12 — UC09/UC10).
 * Atende ao objetivo específico (e) do TCC.
 */
class InvestigacaoController extends Controller
{
    public function __construct(
        private readonly WorkflowEventoService $workflow,
    ) {
    }

    /** T11 — Lista de investigações com filtros. */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Investigacao::class);

        $investigacoes = Investigacao::query()
            ->with(['evento.categoria.pai', 'evento.setorOcorrencia', 'responsavel'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('metodo'), fn ($q) => $q->where('metodo', $request->string('metodo')))
            ->when($request->filled('responsavel_id'), fn ($q) => $q->where('responsavel_id', $request->integer('responsavel_id')))
            ->when($request->boolean('atrasadas'), function ($q) {
                $q->whereNotIn('status', ['concluida', 'cancelada'])
                    ->whereNotNull('data_prevista')
                    ->whereDate('data_prevista', '<', today());
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('investigacoes.index', [
            'investigacoes' => $investigacoes,
            'responsaveis' => User::ativos()->permission('investigacao.gerenciar')->orderBy('nome')->get(),
        ]);
    }

    /** Formulário de abertura de investigação para um evento. */
    public function create(EventoAdverso $evento)
    {
        $this->authorize('create', Investigacao::class);

        abort_if($evento->investigacao !== null, 404, 'Este evento já possui investigação.');
        abort_unless($evento->status === StatusEvento::EmInvestigacao, 404);

        return view('investigacoes.create', [
            'evento' => $evento,
            'responsaveis' => User::ativos()->permission('investigacao.gerenciar')->orderBy('nome')->get(),
        ]);
    }

    /** Abre a investigação (1:1 com o evento). */
    public function store(InvestigacaoRequest $request, EventoAdverso $evento)
    {
        $this->authorize('create', Investigacao::class);

        abort_if($evento->investigacao !== null, 404, 'Este evento já possui investigação.');

        $investigacao = $evento->investigacao()->create([
            ...$request->validated(),
            'status' => StatusInvestigacao::EmAndamento,
        ]);

        return redirect()
            ->route('investigacoes.show', $investigacao)
            ->with('sucesso', 'Investigação aberta. Registre as causas identificadas.');
    }

    /** T12 — Tela da investigação (5 Porquês / Ishikawa + diagrama). */
    public function show(Investigacao $investigacao)
    {
        $this->authorize('view', $investigacao);

        $investigacao->load([
            'evento.categoria.pai', 'evento.setorOcorrencia',
            'causas', 'responsavel', 'acoesCorretivas.responsavel',
        ]);

        return view('investigacoes.show', ['investigacao' => $investigacao]);
    }

    /** Edição dos dados da investigação. */
    public function edit(Investigacao $investigacao)
    {
        $this->authorize('update', $investigacao);

        $investigacao->load('evento');

        return view('investigacoes.edit', [
            'investigacao' => $investigacao,
            'responsaveis' => User::ativos()->permission('investigacao.gerenciar')->orderBy('nome')->get(),
        ]);
    }

    public function update(InvestigacaoRequest $request, Investigacao $investigacao)
    {
        $this->authorize('update', $investigacao);

        $investigacao->update($request->validated());

        return redirect()
            ->route('investigacoes.show', $investigacao)
            ->with('sucesso', 'Investigação atualizada.');
    }

    /**
     * Conclui a investigação — bloqueada sem ≥1 causa raiz e ≥1 ação corretiva.
     * Move o evento para aguardando_acao.
     */
    public function concluir(Request $request, Investigacao $investigacao)
    {
        if (! $investigacao->podeConcluir()) {
            return back()->with('erro', 'Para concluir é necessário marcar ao menos uma causa raiz e cadastrar ao menos uma ação corretiva.');
        }

        $this->authorize('concluir', $investigacao);

        $request->validate(
            [
                'causa_raiz' => ['required', 'string', 'max:5000'],
                'conclusao' => ['required', 'string', 'max:10000'],
            ],
            [],
            ['causa_raiz' => 'síntese da causa raiz', 'conclusao' => 'conclusão']
        );

        $investigacao->update([
            'causa_raiz' => $request->input('causa_raiz'),
            'conclusao' => $request->input('conclusao'),
            'status' => StatusInvestigacao::Concluida,
            'data_conclusao' => today(),
        ]);

        try {
            $this->workflow->transicionar(
                $investigacao->evento,
                StatusEvento::AguardandoAcao,
                $request->user(),
                null,
                'conclusao_investigacao'
            );
        } catch (TransicaoInvalidaException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return redirect()
            ->route('eventos.show', $investigacao->evento)
            ->with('sucesso', 'Investigação concluída — evento aguardando execução das ações.');
    }
}
