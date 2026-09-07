{{-- T10 — Formulário de triagem: relato à esquerda, classificação à direita --}}
@extends('layouts.app')

@section('title', 'Triagem ' . $evento->protocolo . ' · SIGEA')
@section('titulo', 'Triagem do Evento ' . $evento->protocolo)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('triagem.index') }}">Triagem</a></li>
    <li class="breadcrumb-item active">{{ $evento->protocolo }}</li>
@endsection

@section('conteudo')
    <div class="row">
        {{-- Coluna esquerda: relato original (somente leitura) --}}
        <div class="col-lg-5">
            <div class="card card-outline card-secondary">
                <div class="card-header"><h3 class="card-title"><i class="far fa-file-alt mr-1"></i> Relato original</h3></div>
                <div class="card-body">
                    <dl>
                        <dt>Ocorrência</dt>
                        <dd>
                            {{ $evento->data_ocorrencia->format('d/m/Y') }}
                            @if ($evento->hora_ocorrencia) às {{ substr($evento->hora_ocorrencia, 0, 5) }} @endif
                            · {{ $evento->turno->label() }}
                            · {{ $evento->setorOcorrencia->nome }}
                            @if ($evento->local_especifico) — {{ $evento->local_especifico }} @endif
                        </dd>
                        <dt>Categoria informada pelo notificante</dt>
                        <dd>{{ $evento->categoria->nome_completo }}</dd>
                        <dt>Descrição</dt>
                        <dd>{{ $evento->descricao }}</dd>
                        <dt>Fatores contribuintes</dt>
                        <dd>{{ $evento->fatores_contribuintes ?? '—' }}</dd>
                        <dt>Ação imediata</dt>
                        <dd>{{ $evento->acao_imediata ?? '—' }}</dd>
                        <dt>Paciente</dt>
                        <dd>
                            @if ($evento->paciente_envolvido)
                                <code>{{ $evento->paciente_codigo ?? '—' }}</code>
                                · {{ $evento->paciente_faixa_etaria?->label() ?? '—' }}
                                · {{ $evento->paciente_sexo?->label() ?? '—' }}
                            @else
                                Sem paciente envolvido
                            @endif
                        </dd>
                        <dt>Notificante</dt>
                        <dd>{{ $evento->anonima ? 'Anônimo' : ($evento->notificante->nome ?? '—') }}</dd>
                        @if ($evento->anexos->isNotEmpty())
                            <dt>Anexos</dt>
                            <dd>
                                @foreach ($evento->anexos as $anexo)
                                    <a href="{{ route('eventos.anexo.download', [$evento, $anexo]) }}" class="d-block">
                                        <i class="far fa-file mr-1"></i>{{ $anexo->nome_original }}
                                    </a>
                                @endforeach
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Coluna direita: classificação --}}
        <div class="col-lg-7">
            <form method="POST" action="{{ route('triagem.update', $evento) }}">
                @csrf
                @method('PUT')
                <div class="card card-outline card-primary">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-clipboard-check mr-1"></i> Classificação</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="categoria_evento_id">Categoria (revisão) <span class="text-danger">*</span></label>
                            <select name="categoria_evento_id" id="categoria_evento_id" class="form-control" required>
                                @foreach ($categoriasRaiz as $raiz)
                                    <optgroup label="{{ $raiz->codigo }} — {{ $raiz->nome }}">
                                        @foreach ($raiz->filhas as $filha)
                                            <option value="{{ $filha->id }}"
                                                @selected(old('categoria_evento_id', $evento->categoria_evento_id) == $filha->id)>
                                                {{ $filha->codigo }} — {{ $filha->nome }}
                                                @if ($filha->exige_investigacao) (exige investigação) @endif
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="classificacao">
                                    Classificação do incidente <span class="text-danger">*</span>
                                    <i class="fas fa-info-circle text-muted" data-toggle="tooltip"
                                       title="Near miss: erro interceptado antes de atingir o paciente. Evento adverso: incidente com dano. Never event: evento grave que nunca deveria ocorrer." aria-hidden="true"></i>
                                </label>
                                <select name="classificacao" id="classificacao" class="form-control" required>
                                    @foreach (\App\Enums\ClassificacaoIncidente::cases() as $classificacao)
                                        <option value="{{ $classificacao->value }}"
                                            @selected(old('classificacao', $evento->classificacao->value) === $classificacao->value)>
                                            {{ $classificacao->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="grau_dano">
                                    Grau de dano <span class="text-danger">*</span>
                                    <i class="fas fa-info-circle text-muted" data-toggle="tooltip"
                                       title="Escala WHO/ICPS: nenhum, leve (desconforto passageiro), moderado (intervenção adicional), grave (intervenção de suporte à vida/dano permanente), óbito." aria-hidden="true"></i>
                                </label>
                                <select name="grau_dano" id="grau_dano" class="form-control" required>
                                    @foreach (\App\Enums\GrauDano::cases() as $grau)
                                        <option value="{{ $grau->value }}"
                                            @selected(old('grau_dano', $evento->grau_dano->value) === $grau->value)>
                                            {{ $grau->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="evitabilidade">
                                Evitabilidade <span class="text-danger">*</span>
                                <i class="fas fa-info-circle text-muted" data-toggle="tooltip"
                                   title="Julgamento sobre a possibilidade de o evento ter sido evitado com as barreiras adequadas." aria-hidden="true"></i>
                            </label>
                            <select name="evitabilidade" id="evitabilidade" class="form-control" required>
                                <option value="">Avaliar…</option>
                                @foreach (\App\Enums\Evitabilidade::cases() as $evitabilidade)
                                    @continue($evitabilidade === \App\Enums\Evitabilidade::NaoAvaliado)
                                    <option value="{{ $evitabilidade->value }}"
                                        @selected(old('evitabilidade', $evento->evitabilidade->value) === $evitabilidade->value)>
                                        {{ $evitabilidade->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <fieldset class="border rounded p-3 mb-3">
                            <legend class="w-auto px-2 h6 mb-0">
                                Matriz de risco
                                <i class="fas fa-info-circle text-muted" data-toggle="tooltip"
                                   title="Score = probabilidade × severidade. 1–4 baixo · 5–9 moderado · 10–15 alto · 16–25 extremo." aria-hidden="true"></i>
                            </legend>
                            <div class="form-row align-items-center">
                                <div class="form-group col-md-5">
                                    <label for="probabilidade">Probabilidade: <output id="probabilidade-valor">{{ old('probabilidade', $evento->probabilidade ?? 3) }}</output></label>
                                    <input type="range" name="probabilidade" id="probabilidade" class="custom-range"
                                           min="1" max="5" step="1" required
                                           value="{{ old('probabilidade', $evento->probabilidade ?? 3) }}"
                                           aria-describedby="legenda-prob">
                                    <small id="legenda-prob" class="text-muted d-flex justify-content-between">
                                        <span>Rara</span><span>Frequente</span>
                                    </small>
                                </div>
                                <div class="form-group col-md-5">
                                    <label for="severidade">Severidade: <output id="severidade-valor">{{ old('severidade', $evento->severidade ?? 3) }}</output></label>
                                    <input type="range" name="severidade" id="severidade" class="custom-range"
                                           min="1" max="5" step="1" required
                                           value="{{ old('severidade', $evento->severidade ?? 3) }}"
                                           aria-describedby="legenda-sev">
                                    <small id="legenda-sev" class="text-muted d-flex justify-content-between">
                                        <span>Mínima</span><span>Catastrófica</span>
                                    </small>
                                </div>
                                <div class="form-group col-md-2 text-center">
                                    <div class="text-muted small">Score</div>
                                    <div class="h3 mb-0" id="score-risco" aria-live="polite">—</div>
                                    <span class="badge badge-light" id="nivel-risco" aria-live="polite">—</span>
                                </div>
                            </div>
                        </fieldset>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="prioridade">Prioridade (sugerida pelo risco)</label>
                                <select name="prioridade" id="prioridade" class="form-control">
                                    @foreach (\App\Enums\Prioridade::cases() as $prioridade)
                                        <option value="{{ $prioridade->value }}"
                                            @selected(old('prioridade', $evento->prioridade->value) === $prioridade->value)>
                                            {{ $prioridade->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="prazo_conclusao">Prazo de conclusão (auto pelo SLA, editável)</label>
                                <input type="date" name="prazo_conclusao" id="prazo_conclusao" class="form-control"
                                       min="{{ today()->toDateString() }}"
                                       value="{{ old('prazo_conclusao', $evento->prazo_conclusao?->toDateString()) }}"
                                       @if ($evento->prazo_conclusao) data-manual="1" @endif>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label for="feedback_notificante">Devolutiva ao notificante</label>
                            <textarea name="feedback_notificante" id="feedback_notificante" class="form-control" rows="2"
                                      placeholder="Mensagem de retorno a quem notificou (combate à subnotificação)">{{ old('feedback_notificante', $evento->feedback_notificante) }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary"><i class="far fa-save mr-1"></i> Salvar classificação</button>
                    </div>
                </div>
            </form>

            {{-- Encaminhamentos (habilitados após classificação salva) --}}
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-route mr-1"></i> Encaminhamento</h3></div>
                <div class="card-body">
                    @php($classificado = $evento->classificacaoCompleta())
                    @unless ($classificado)
                        <p class="text-muted small mb-2">
                            <i class="fas fa-lock mr-1"></i> Salve a classificação completa para habilitar os encaminhamentos.
                        </p>
                    @endunless

                    <form method="POST" action="{{ route('triagem.encaminhar', $evento) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="destino" value="em_investigacao">
                        <button class="btn btn-warning" @disabled(! $classificado)
                                onclick="return confirm('Encaminhar o evento para investigação de causa raiz?')">
                            <i class="fas fa-search mr-1"></i> Encaminhar para investigação
                        </button>
                    </form>

                    <form method="POST" action="{{ route('triagem.encaminhar', $evento) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="destino" value="aguardando_acao">
                        <button class="btn btn-outline-warning"
                                @disabled(! $classificado || $evento->categoria->exige_investigacao)
                                title="{{ $evento->categoria->exige_investigacao ? 'Esta categoria exige investigação.' : '' }}"
                                onclick="return confirm('Dispensar investigação e ir direto para ações corretivas?')">
                            Dispensar investigação → ações
                        </button>
                    </form>

                    <form method="POST" action="{{ route('triagem.concluir', $evento) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-success"
                                @disabled(! $classificado || $evento->grau_dano !== \App\Enums\GrauDano::Nenhum || ! in_array($evento->classificacao, [\App\Enums\ClassificacaoIncidente::NearMiss, \App\Enums\ClassificacaoIncidente::CircunstanciaNotificavel], true))
                                title="Permitido apenas para near miss ou circunstância notificável sem dano"
                                onclick="return confirm('Concluir o evento diretamente na triagem?')">
                            <i class="fas fa-flag-checkered mr-1"></i> Concluir direto
                        </button>
                    </form>

                    <button class="btn btn-outline-danger" data-toggle="modal" data-target="#modal-cancelar-triagem">
                        <i class="fas fa-ban mr-1"></i> Cancelar evento
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de cancelamento na triagem (justificativa obrigatória) --}}
    <div class="modal fade" id="modal-cancelar-triagem" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('eventos.cancelar', $evento) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Cancelar evento {{ $evento->protocolo }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label for="justificativa-triagem">Justificativa <span class="text-danger">*</span></label>
                        <textarea name="justificativa" id="justificativa-triagem" class="form-control" rows="3" required
                                  placeholder="Ex.: duplicidade do EA-2026-000123"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Voltar</button>
                    <button type="submit" class="btn btn-danger">Confirmar cancelamento</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
@endpush
