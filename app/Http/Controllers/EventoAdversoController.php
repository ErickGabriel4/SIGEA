<?php

namespace App\Http\Controllers;

use App\Enums\StatusEvento;
use App\Http\Requests\EventoAdversoRequest;
use App\Models\CategoriaEvento;
use App\Models\Configuracao;
use App\Models\EventoAdverso;
use App\Models\EventoHistorico;
use App\Models\Setor;
use App\Models\User;
use App\Services\PseudonimizadorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Registro e acompanhamento de eventos adversos (T04 a T08).
 * Atende ao objetivo específico (e) do TCC — desenvolver a plataforma com
 * registro, classificação e acompanhamento de eventos adversos.
 */
class EventoAdversoController extends Controller
{
    public function __construct(
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

        // Notificação anônima não passa por rascunho: vai direto ao NSP
        $enviar = $request->input('acao_formulario') === 'enviar' || $anonima;

        $evento = DB::transaction(function () use ($request, $dados, $anonima) {
            $evento = EventoAdverso::create([
                ...collect($dados)->except(['profissionais', 'anonima'])->all(),
                'anonima' => $anonima,
                'notificante_id' => $anonima ? null : $request->user()->id,
                'setor_notificante_id' => $anonima ? null : $request->user()->setor_id,
                'paciente_codigo' => $this->pseudonimizador->codificar($dados['paciente_codigo'] ?? null),
                'status' => StatusEvento::Rascunho,
            ]);

            foreach ($dados['profissionais'] ?? [] as $profissional) {
                $evento->profissionais()->create($profissional);
            }

            return $evento;
        });

        if ($enviar) {
            $this->enviarAoNucleo($evento, $anonima ? null : $request->user()->id);

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
            'profissionais.usuario', 'anexos.usuario', 'historico.usuario',
        ]);

        $comentarios = $evento->comentarios()
            ->visiveisPara($request->user())
            ->with('autor')
            ->get();

        return view('eventos.show', ['evento' => $evento, 'comentarios' => $comentarios]);
    }

    /** T08 — Edição de rascunho (mesmo wizard, pré-preenchido). */
    public function edit(EventoAdverso $evento)
    {
        $this->authorize('update', $evento);

        $evento->load('profissionais');

        return view('eventos.edit', ['evento' => $evento] + $this->dadosFormulario());
    }

    /** Atualiza rascunho. */
    public function update(EventoAdversoRequest $request, EventoAdverso $evento)
    {
        $this->authorize('update', $evento);

        $dados = $request->validated();

        DB::transaction(function () use ($dados, $evento) {
            $evento->update([
                ...collect($dados)->except(['profissionais', 'anonima'])->all(),
                'paciente_codigo' => $this->pseudonimizador->codificar($dados['paciente_codigo'] ?? null),
            ]);

            $evento->profissionais()->delete();

            foreach ($dados['profissionais'] ?? [] as $profissional) {
                $evento->profissionais()->create($profissional);
            }
        });

        if ($request->input('acao_formulario') === 'enviar') {
            $this->enviarAoNucleo($evento, $request->user()->id);

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

        $this->enviarAoNucleo($evento, $request->user()->id);

        return view('eventos.confirmacao', ['evento' => $evento, 'anonima' => false]);
    }

    /**
     * Transição rascunho → notificado: encaminha o evento ao NSP e registra
     * a linha correspondente na trilha do workflow.
     */
    private function enviarAoNucleo(EventoAdverso $evento, ?int $userId): void
    {
        DB::transaction(function () use ($evento, $userId) {
            $statusAnterior = $evento->status;

            $evento->update(['status' => StatusEvento::Notificado]);

            EventoHistorico::create([
                'evento_adverso_id' => $evento->id,
                'user_id' => $userId,
                'status_anterior' => $statusAnterior->value,
                'status_novo' => StatusEvento::Notificado->value,
                'acao' => 'envio',
            ]);
        });
    }

    /** Dados comuns aos formulários do wizard. */
    private function dadosFormulario(): array
    {
        return [
            'categoriasRaiz' => CategoriaEvento::raizes()->ativas()->with('filhas')->get(),
            'setores' => Setor::ativos()->orderBy('nome')->get(),
            'usuarios' => User::ativos()->orderBy('nome')->get(['id', 'nome', 'categoria_profissional']),
            'permiteAnonima' => (bool) Configuracao::obter('permite_notificacao_anonima', true),
        ];
    }
}
