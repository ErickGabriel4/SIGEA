<?php

namespace App\Http\Controllers;

use App\Enums\StatusEvento;
use App\Exceptions\TransicaoInvalidaException;
use App\Http\Requests\TriagemRequest;
use App\Models\CategoriaEvento;
use App\Models\Configuracao;
use App\Models\EventoAdverso;
use App\Services\MatrizRiscoService;
use App\Services\NotivisaService;
use App\Services\WorkflowEventoService;
use Illuminate\Http\Request;

/**
 * Triagem e classificação dos eventos notificados (T09/T10).
 * Atende ao objetivo específico (e) do TCC.
 */
class TriagemController extends Controller
{
    public function __construct(
        private readonly WorkflowEventoService $workflow,
        private readonly MatrizRiscoService $matrizRisco,
        private readonly NotivisaService $notivisa,
    ) {
    }

    /** T09 — Fila de triagem ordenada por tempo de espera, com destaque de SLA estourado. */
    public function index()
    {
        $slaHoras = (int) Configuracao::obter('sla_triagem_horas', 48);

        $eventos = EventoAdverso::pendentesTriagem()
            ->with(['categoria.pai', 'setorOcorrencia'])
            ->orderBy('created_at')
            ->paginate(15);

        return view('triagem.index', [
            'eventos' => $eventos,
            'slaHoras' => $slaHoras,
            'limiteSla' => now()->subHours($slaHoras),
        ]);
    }

    /** Gestor assume o evento (notificado → em_triagem). */
    public function assumir(Request $request, EventoAdverso $evento)
    {
        $evento->gestor_responsavel_id = $request->user()->id;

        try {
            $this->workflow->transicionar($evento, StatusEvento::EmTriagem, $request->user(), null, 'assumir_triagem');
        } catch (TransicaoInvalidaException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return redirect()
            ->route('triagem.edit', $evento)
            ->with('sucesso', "Você assumiu o evento {$evento->protocolo}.");
    }

    /** T10 — Formulário de classificação (relato à esquerda, classificação à direita). */
    public function edit(EventoAdverso $evento)
    {
        abort_unless(in_array($evento->status, [StatusEvento::EmTriagem, StatusEvento::Notificado], true), 404);

        $evento->load(['categoria.pai', 'setorOcorrencia', 'profissionais.usuario', 'anexos']);

        return view('triagem.edit', [
            'evento' => $evento,
            'categoriasRaiz' => CategoriaEvento::raizes()->ativas()->with('filhas')->get(),
            'slas' => MatrizRiscoService::SLA_DIAS,
        ]);
    }

    /** Salva a classificação e aplica matriz de risco + elegibilidade NOTIVISA. */
    public function update(TriagemRequest $request, EventoAdverso $evento)
    {
        abort_unless($evento->status === StatusEvento::EmTriagem, 404);

        $evento->fill($request->validated());

        // Matriz de risco: nível, prioridade sugerida e prazo pelo SLA
        $this->matrizRisco->aplicar($evento);

        // Prioridade e prazo informados manualmente prevalecem
        if ($request->filled('prioridade')) {
            $evento->prioridade = $request->enum('prioridade', \App\Enums\Prioridade::class);
        }
        if ($request->filled('prazo_conclusao')) {
            $evento->prazo_conclusao = $request->date('prazo_conclusao');
        }

        // Elegibilidade de notificação compulsória à ANVISA
        $this->notivisa->aplicar($evento);

        $evento->save();

        return back()->with('sucesso', 'Classificação salva. Escolha o encaminhamento do evento.');
    }

    /** Encaminha: em_triagem → em_investigacao | aguardando_acao. */
    public function encaminhar(Request $request, EventoAdverso $evento)
    {
        $destino = $request->input('destino') === 'aguardando_acao'
            ? StatusEvento::AguardandoAcao
            : StatusEvento::EmInvestigacao;

        // Categoria com investigação obrigatória não permite dispensa
        if ($destino === StatusEvento::AguardandoAcao && $evento->categoria?->exige_investigacao) {
            return back()->with('erro', 'Esta categoria de evento exige investigação — não é possível dispensá-la.');
        }

        try {
            $this->workflow->transicionar($evento, $destino, $request->user(), $request->input('observacao'), 'encaminhamento');
        } catch (TransicaoInvalidaException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return redirect()
            ->route('eventos.show', $evento)
            ->with('sucesso', "Evento encaminhado: {$destino->label()}.");
    }

    /** Conclusão direta na triagem (near miss / circunstância sem dano). */
    public function concluir(Request $request, EventoAdverso $evento)
    {
        try {
            $this->workflow->transicionar($evento, StatusEvento::Concluido, $request->user(), $request->input('observacao'), 'conclusao_triagem');
        } catch (TransicaoInvalidaException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return redirect()
            ->route('eventos.show', $evento)
            ->with('sucesso', "Evento {$evento->protocolo} concluído na triagem.");
    }

    /** Devolutiva ao notificante (combate à subnotificação). */
    public function feedback(Request $request, EventoAdverso $evento)
    {
        $request->validate(
            ['feedback_notificante' => ['required', 'string', 'max:5000']],
            [],
            ['feedback_notificante' => 'devolutiva']
        );

        $evento->update(['feedback_notificante' => $request->input('feedback_notificante')]);

        return back()->with('sucesso', 'Devolutiva registrada.');
    }

    /** Registra o protocolo NOTIVISA informado manualmente pelo gestor (UC22). */
    public function registrarNotivisa(Request $request, EventoAdverso $evento)
    {
        $request->validate(
            [
                'protocolo_notivisa' => ['required', 'string', 'max:50'],
                'notificado_anvisa_em' => ['nullable', 'date', 'before_or_equal:today'],
            ],
            [],
            ['protocolo_notivisa' => 'protocolo NOTIVISA', 'notificado_anvisa_em' => 'data da notificação']
        );

        $this->notivisa->registrarProtocolo(
            $evento,
            $request->input('protocolo_notivisa'),
            $request->input('notificado_anvisa_em')
        );

        return back()->with('sucesso', 'Protocolo NOTIVISA registrado.');
    }
}
