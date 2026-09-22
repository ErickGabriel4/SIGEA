<?php

namespace App\Http\Controllers;

use App\Enums\StatusEvento;
use App\Exceptions\TransicaoInvalidaException;
use App\Exports\EventosExport;
use App\Http\Requests\EventoAdversoRequest;
use App\Models\CategoriaEvento;
use App\Models\EventoAdverso;
use App\Models\Setor;
use App\Models\User;
use App\Services\PseudonimizadorService;
use App\Services\WorkflowEventoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Registro e acompanhamento de eventos adversos.
 * Atende ao objetivo específico (e) do TCC — desenvolver a plataforma com
 * registro, classificação e acompanhamento de eventos adversos.
 */
class EventoAdversoController extends Controller
{
    public function __construct(
        private readonly WorkflowEventoService $workflow,
        private readonly PseudonimizadorService $pseudonimizador,
    ) {
    }

    /** T05 — Lista de eventos com filtros (escopo por perfil). */
    public function index(Request $request)
    {
        $this->authorize('viewAny', EventoAdverso::class);

        $eventos = EventoAdverso::query()
            ->visiveisPara($request->user())
            ->with(['categoria.pai', 'setorOcorrencia', 'gestorResponsavel'])
            ->when($request->filled('protocolo'), fn ($q) => $q->where('protocolo', 'like', '%' . $request->string('protocolo') . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('setor_id'), fn ($q) => $q->where('setor_ocorrencia_id', $request->integer('setor_id')))
            ->when($request->filled('categoria_id'), function ($q) use ($request) {
                $id = $request->integer('categoria_id');
                $q->where(function ($sub) use ($id) {
                    $sub->where('categoria_evento_id', $id)
                        ->orWhereHas('categoria', fn ($c) => $c->where('categoria_pai_id', $id));
                });
            })
            ->when($request->filled('grau_dano'), fn ($q) => $q->where('grau_dano', $request->string('grau_dano')))
            ->when($request->filled('prioridade'), fn ($q) => $q->where('prioridade', $request->string('prioridade')))
            ->when($request->filled('nivel_risco'), fn ($q) => $q->where('nivel_risco', $request->string('nivel_risco')))
            ->when($request->filled('gestor_id'), fn ($q) => $q->where('gestor_responsavel_id', $request->integer('gestor_id')))
            ->when($request->filled('probabilidade'), fn ($q) => $q->where('probabilidade', $request->integer('probabilidade')))
            ->when($request->filled('severidade'), fn ($q) => $q->where('severidade', $request->integer('severidade')))
            ->noPeriodo($request->input('inicio'), $request->input('fim'))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('eventos.index', [
            'eventos' => $eventos,
            'setores' => Setor::ativos()->orderBy('nome')->get(),
            'categoriasRaiz' => CategoriaEvento::raizes()->ativas()->get(),
            'gestores' => User::ativos()->permission('evento.triar')->orderBy('nome')->get(),
        ]);
    }

    /** T06 — Minhas notificações (notificador). */
    public function meus(Request $request)
    {
        $eventos = EventoAdverso::query()
            ->where('notificante_id', $request->user()->id)
            ->where('anonima', false)
            ->with(['categoria.pai', 'setorOcorrencia'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('eventos.meus', ['eventos' => $eventos]);
    }

    /** T04 — Wizard de notificação. */
    public function create()
    {
        $this->authorize('create', EventoAdverso::class);

        return view('eventos.create', $this->dadosFormulario());
    }

    /** Persiste a notificação (rascunho ou envio direto). */
    public function store(EventoAdversoRequest $request)
    {
        $this->authorize('create', EventoAdverso::class);

        $dados = $request->validated();
        $anonima = (bool) ($dados['anonima'] ?? false);
        $enviar = $request->input('acao_formulario') === 'enviar' || $anonima;

        $evento = DB::transaction(function () use ($request, $dados, $anonima) {
            $evento = new EventoAdverso([
                ...collect($dados)->except(['profissionais', 'anexos', 'anonima'])->all(),
                'anonima' => $anonima,
                'notificante_id' => $anonima ? null : $request->user()->id,
                'setor_notificante_id' => $request->user()->setor_id,
                'paciente_codigo' => $this->pseudonimizador->codificar($dados['paciente_codigo'] ?? null),
                'status' => StatusEvento::Rascunho,
            ]);

            // Anonimato: nenhum registro de autoria na trilha de auditoria
            if ($anonima) {
                $evento->disableLogging();
            }

            $evento->save();

            foreach ($dados['profissionais'] ?? [] as $profissional) {
                $evento->profissionais()->create($profissional);
            }

            foreach ($request->file('anexos', []) as $arquivo) {
                $this->salvarAnexo($evento, $arquivo, $anonima ? null : $request->user()->id);
            }

            return $evento;
        });

        if ($enviar) {
            $this->workflow->transicionar(
                $evento,
                StatusEvento::Notificado,
                $anonima ? null : $request->user(),
                null,
                'envio'
            );

            return view('eventos.confirmacao', ['evento' => $evento, 'anonima' => $anonima]);
        }

        return redirect()
            ->route('eventos.edit', $evento)
            ->with('sucesso', 'Rascunho salvo. Você pode concluir a notificação quando quiser.');
    }

    /** T07 — Detalhe do evento com abas. */
    public function show(Request $request, EventoAdverso $evento)
    {
        $this->authorize('view', $evento);

        $evento->load([
            'categoria.pai', 'setorOcorrencia', 'setorNotificante', 'notificante',
            'gestorResponsavel', 'profissionais.usuario', 'anexos',
            'historico.usuario', 'investigacao.causas', 'investigacao.responsavel',
            'acoesCorretivas.responsavel',
        ]);

        $comentarios = $evento->comentarios()
            ->visiveisPara($request->user())
            ->with('autor')
            ->get();

        return view('eventos.show', [
            'evento' => $evento,
            'comentarios' => $comentarios,
        ]);
    }

    /** T08 — Edição de rascunho (mesmo wizard, pré-preenchido). */
    public function edit(EventoAdverso $evento)
    {
        $this->authorize('update', $evento);

        $evento->load(['profissionais', 'anexos']);

        return view('eventos.edit', ['evento' => $evento] + $this->dadosFormulario());
    }

    /** Atualiza rascunho. */
    public function update(EventoAdversoRequest $request, EventoAdverso $evento)
    {
        $this->authorize('update', $evento);

        $dados = $request->validated();

        DB::transaction(function () use ($request, $dados, $evento) {
            $evento->fill([
                ...collect($dados)->except(['profissionais', 'anexos', 'anonima'])->all(),
                'paciente_codigo' => $this->pseudonimizador->codificar($dados['paciente_codigo'] ?? null),
            ]);
            $evento->save();

            $evento->profissionais()->delete();
            foreach ($dados['profissionais'] ?? [] as $profissional) {
                $evento->profissionais()->create($profissional);
            }

            foreach ($request->file('anexos', []) as $arquivo) {
                $this->salvarAnexo($evento, $arquivo, $request->user()->id);
            }
        });

        if ($request->input('acao_formulario') === 'enviar') {
            $this->workflow->transicionar($evento, StatusEvento::Notificado, $request->user(), null, 'envio');

            return view('eventos.confirmacao', ['evento' => $evento, 'anonima' => false]);
        }

        return redirect()
            ->route('eventos.edit', $evento)
            ->with('sucesso', 'Rascunho atualizado.');
    }

    /** Exclui rascunho (apenas dono). */
    public function destroy(EventoAdverso $evento)
    {
        $this->authorize('delete', $evento);

        $evento->delete();

        return redirect()
            ->route('eventos.meus')
            ->with('sucesso', 'Rascunho excluído.');
    }

    /** Envia rascunho existente (rascunho → notificado). */
    public function enviar(Request $request, EventoAdverso $evento)
    {
        $this->authorize('enviar', $evento);

        try {
            $this->workflow->transicionar($evento, StatusEvento::Notificado, $request->user(), null, 'envio');
        } catch (TransicaoInvalidaException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return view('eventos.confirmacao', ['evento' => $evento, 'anonima' => false]);
    }

    /** Cancela evento (rascunho pelo dono; triagem pelo gestor, com justificativa). */
    public function cancelar(Request $request, EventoAdverso $evento)
    {
        $this->authorize('cancelar', $evento);

        $request->validate(
            ['justificativa' => [$evento->status === StatusEvento::Rascunho ? 'nullable' : 'required', 'string', 'max:1000']],
            [],
            ['justificativa' => 'justificativa']
        );

        try {
            $this->workflow->transicionar(
                $evento,
                StatusEvento::Cancelado,
                $request->user(),
                $request->input('justificativa'),
                'cancelamento'
            );
        } catch (TransicaoInvalidaException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return redirect()
            ->route('eventos.index')
            ->with('sucesso', "Evento {$evento->protocolo} cancelado.");
    }

    /** Exporta a lista filtrada em CSV/XLSX. */
    public function exportar(Request $request)
    {
        $formato = $request->input('formato', 'xlsx') === 'csv' ? 'csv' : 'xlsx';

        return Excel::download(
            new EventosExport($request->all()),
            'eventos_adversos_' . now()->format('Y-m-d_His') . '.' . $formato
        );
    }

    /** Ficha do evento em PDF. */
    public function pdf(Request $request, EventoAdverso $evento)
    {
        $this->authorize('view', $evento);

        $evento->load([
            'categoria.pai', 'setorOcorrencia', 'notificante', 'gestorResponsavel',
            'profissionais.usuario', 'historico.usuario',
            'investigacao.causas', 'acoesCorretivas.responsavel',
        ]);

        return Pdf::loadView('eventos.pdf', ['evento' => $evento])
            ->setPaper('a4')
            ->download("evento_{$evento->protocolo}.pdf");
    }

    /** Dados comuns aos formulários do wizard. */
    private function dadosFormulario(): array
    {
        return [
            'categoriasRaiz' => CategoriaEvento::raizes()->ativas()->with('filhas')->get(),
            'setores' => Setor::ativos()->orderBy('nome')->get(),
            'usuarios' => User::ativos()->orderBy('nome')->get(['id', 'nome', 'categoria_profissional']),
            'permiteAnonima' => (bool) \App\Models\Configuracao::obter('permite_notificacao_anonima', true),
        ];
    }

    /** Armazena um anexo no storage privado com nome randomizado. */
    private function salvarAnexo(EventoAdverso $evento, $arquivo, ?int $userId): void
    {
        $caminho = $arquivo->store("privado/eventos/{$evento->id}", 'local');

        $evento->anexos()->create([
            'user_id' => $userId,
            'nome_original' => $arquivo->getClientOriginalName(),
            'caminho' => $caminho,
            'mime' => $arquivo->getMimeType(),
            'tamanho' => $arquivo->getSize(),
        ]);
    }
}
