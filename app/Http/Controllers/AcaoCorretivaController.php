<?php

namespace App\Http\Controllers;

use App\Enums\Eficacia;
use App\Enums\StatusAcao;
use App\Http\Requests\AcaoCorretivaRequest;
use App\Models\AcaoCorretiva;
use App\Models\EventoAdverso;
use App\Models\Setor;
use App\Models\User;
use App\Notifications\AcaoCorretivaAtribuida;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ações corretivas: cadastro, execução e avaliação de eficácia.
 */
class AcaoCorretivaController extends Controller
{
    /** Lista geral de ações (gestores). */
    public function index(Request $request)
    {
        $this->authorize('viewAny', AcaoCorretiva::class);

        $acoes = AcaoCorretiva::query()
            ->with(['evento', 'responsavel', 'setor'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('responsavel_id'), fn ($q) => $q->where('responsavel_id', $request->integer('responsavel_id')))
            ->when($request->filled('setor_id'), fn ($q) => $q->where('setor_id', $request->integer('setor_id')))
            ->when($request->filled('prazo_ate'), fn ($q) => $q->whereDate('prazo', '<=', $request->input('prazo_ate')))
            ->orderByRaw("CASE WHEN status = 'atrasada' THEN 0 ELSE 1 END")
            ->orderBy('prazo')
            ->paginate(15)
            ->withQueryString();

        return view('acoes.index', [
            'acoes' => $acoes,
            'responsaveis' => User::ativos()->orderBy('nome')->get(['id', 'nome']),
            'setores' => Setor::ativos()->orderBy('nome')->get(),
        ]);
    }

    /** Minhas ações (responsável). */
    public function minhas(Request $request)
    {
        $this->authorize('viewAny', AcaoCorretiva::class);

        $acoes = AcaoCorretiva::query()
            ->doResponsavel($request->user())
            ->with(['evento'])
            ->orderByRaw("CASE WHEN status IN ('pendente','em_andamento','atrasada') THEN 0 ELSE 1 END")
            ->orderBy('prazo')
            ->paginate(15);

        return view('acoes.minhas', ['acoes' => $acoes]);
    }

    /** Formulário de nova ação para um evento. */
    public function create(EventoAdverso $evento)
    {
        $this->authorize('create', AcaoCorretiva::class);

        return view('acoes.create', [
            'evento' => $evento,
            'responsaveis' => User::ativos()->orderBy('nome')->get(['id', 'nome', 'cargo']),
            'setores' => Setor::ativos()->orderBy('nome')->get(),
        ]);
    }

    /** Cadastra a ação e notifica o responsável designado. */
    public function store(AcaoCorretivaRequest $request, EventoAdverso $evento)
    {
        $this->authorize('create', AcaoCorretiva::class);

        $acao = $evento->acoesCorretivas()->create([
            ...$request->validated(),
            'investigacao_id' => $evento->investigacao?->id,
            'status' => StatusAcao::Pendente,
        ]);

        $acao->responsavel->notify(new AcaoCorretivaAtribuida($acao));

        return redirect()
            ->route('eventos.show', ['evento' => $evento, 'aba' => 'acoes'])
            ->with('sucesso', 'Ação corretiva cadastrada e responsável notificado.');
    }

    public function edit(AcaoCorretiva $acao)
    {
        $this->authorize('update', $acao);

        $acao->load('evento');

        return view('acoes.edit', [
            'acao' => $acao,
            'responsaveis' => User::ativos()->orderBy('nome')->get(['id', 'nome', 'cargo']),
            'setores' => Setor::ativos()->orderBy('nome')->get(),
        ]);
    }

    public function update(AcaoCorretivaRequest $request, AcaoCorretiva $acao)
    {
        $this->authorize('update', $acao);

        $responsavelAnterior = $acao->responsavel_id;

        $acao->update($request->validated());

        if ($acao->status === StatusAcao::Atrasada && $acao->prazo->isFuture()) {
            $acao->update(['status' => StatusAcao::EmAndamento]);
        }

        if ($acao->responsavel_id !== $responsavelAnterior) {
            $acao->responsavel->notify(new AcaoCorretivaAtribuida($acao));
        }

        return redirect()
            ->route('eventos.show', ['evento' => $acao->evento_adverso_id, 'aba' => 'acoes'])
            ->with('sucesso', 'Ação corretiva atualizada.');
    }

    /** Conclusão exige evidência obrigatória. */
    public function concluir(Request $request, AcaoCorretiva $acao)
    {
        $this->authorize('concluir', $acao);

        $request->validate(
            ['evidencia_conclusao' => ['required', 'string', 'min:10', 'max:5000']],
            [
                'evidencia_conclusao.required' => 'A evidência de execução é obrigatória para concluir a ação.',
                'evidencia_conclusao.min' => 'Descreva a evidência com mais detalhes (mínimo 10 caracteres).',
            ],
            ['evidencia_conclusao' => 'evidência de conclusão']
        );

        $acao->update([
            'evidencia_conclusao' => $request->input('evidencia_conclusao'),
            'data_conclusao' => today(),
            'status' => StatusAcao::Concluida,
        ]);

        return back()->with('sucesso', "Ação \"{$acao->titulo}\" concluída.");
    }

    /** Avaliação de eficácia pelo gestor. */
    public function avaliar(Request $request, AcaoCorretiva $acao)
    {
        $this->authorize('avaliar', $acao);

        $request->validate(
            ['eficacia' => ['required', Rule::enum(Eficacia::class), Rule::notIn(['nao_avaliada'])]],
            [],
            ['eficacia' => 'eficácia']
        );

        $acao->update(['eficacia' => $request->input('eficacia')]);

        return back()->with('sucesso', 'Eficácia da ação registrada.');
    }

    public function destroy(AcaoCorretiva $acao)
    {
        $this->authorize('delete', $acao);

        $eventoId = $acao->evento_adverso_id;
        $acao->delete();

        return redirect()
            ->route('eventos.show', ['evento' => $eventoId, 'aba' => 'acoes'])
            ->with('sucesso', 'Ação corretiva removida.');
    }
}
