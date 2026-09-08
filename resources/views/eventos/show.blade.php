{{-- T07 — Detalhe do evento (UC05) --}}
@extends('layouts.app')

@section('title', $evento->protocolo . ' · SIGEA')
@section('titulo', 'Evento ' . $evento->protocolo)
@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ auth()->user()->can('evento.ver_todos') ? route('eventos.index') : route('eventos.meus') }}">Eventos</a>
    </li>
    <li class="breadcrumb-item active">{{ $evento->protocolo }}</li>
@endsection

@php($abaAtiva = request('aba', 'resumo'))
@php($ehGestor = auth()->user()->can('evento.ver_todos'))

@section('conteudo')
    {{-- Cabeçalho com badges --}}
    <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="h5 mb-1">{{ $evento->categoria->nome_completo }}</h2>
                <div>
                    <x-badge-status :status="$evento->status" />
                    <x-badge-dano :dano="$evento->grau_dano" />
                    <x-badge-prioridade :prioridade="$evento->prioridade" />
                    @if ($evento->anonima)
                        <span class="badge badge-secondary"><i class="fas fa-user-secret mr-1"></i>Anônima</span>
                    @endif
                </div>
            </div>
            <div class="mt-2 mt-md-0">
                @can('update', $evento)
                    <a href="{{ route('eventos.edit', $evento) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-pen mr-1"></i> Editar rascunho
                    </a>
                @endcan

                @can('enviar', $evento)
                    <form method="POST" action="{{ route('eventos.enviar', $evento) }}" class="d-inline"
                          onsubmit="return confirm('Enviar a notificação para triagem do NSP?')">
                        @csrf
                        <button class="btn btn-success btn-sm"><i class="fas fa-paper-plane mr-1"></i> Enviar</button>
                    </form>
                @endcan

                @if ($ehGestor && $evento->status === \App\Enums\StatusEvento::Notificado)
                    @can('evento.triar')
                        <form method="POST" action="{{ route('triagem.assumir', $evento) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-primary btn-sm"><i class="fas fa-inbox mr-1"></i> Assumir triagem</button>
                        </form>
                    @endcan
                @endif

                @if ($ehGestor && $evento->status === \App\Enums\StatusEvento::EmTriagem)
                    <a href="{{ route('triagem.edit', $evento) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-clipboard-check mr-1"></i> Continuar triagem
                    </a>
                @endif

                @if ($ehGestor && $evento->status === \App\Enums\StatusEvento::EmInvestigacao && $evento->investigacao === null)
                    @can('investigacao.gerenciar')
                        <a href="{{ route('investigacoes.create', $evento) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-search mr-1"></i> Abrir investigação
                        </a>
                    @endcan
                @endif

                @if ($ehGestor && $evento->status === \App\Enums\StatusEvento::AguardandoAcao)
                    @can('acao.gerenciar')
                        <a href="{{ route('acoes.create', $evento) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-plus mr-1"></i> Nova ação
                        </a>
                    @endcan
                    @can('evento.concluir')
                        <form method="POST" action="{{ route('triagem.concluir', $evento) }}" class="d-inline"
                              onsubmit="return confirm('Concluir o evento? Todas as ações devem estar tratadas.')">
                            @csrf
                            <button class="btn btn-success btn-sm"><i class="fas fa-flag-checkered mr-1"></i> Concluir evento</button>
                        </form>
                    @endcan
                @endif

                @can('cancelar', $evento)
                    <button class="btn btn-outline-danger btn-sm" data-toggle="modal" data-target="#modal-cancelar">
                        <i class="fas fa-ban mr-1"></i> Cancelar
                    </button>
                @endcan
            </div>
        </div>
    </div>

    {{-- Abas --}}
    <div class="card card-primary card-outline card-outline-tabs">
        <div class="card-header p-0 border-bottom-0">
            <ul class="nav nav-tabs" role="tablist">
                @foreach ([
                    'resumo' => 'Resumo',
                    'envolvidos' => 'Envolvidos',
                    'classificacao' => 'Classificação',
                    'investigacao' => 'Investigação',
                    'acoes' => 'Ações corretivas',
                    'anexos' => 'Anexos',
                    'comentarios' => 'Comentários',
                    'historico' => 'Histórico',
                ] as $aba => $rotulo)
                    <li class="nav-item">
                        <a class="nav-link @if ($abaAtiva === $aba) active @endif" data-toggle="pill"
                           href="#aba-{{ $aba }}" role="tab">{{ $rotulo }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">

                {{-- ── Resumo ── --}}
                <div class="tab-pane fade @if ($abaAtiva === 'resumo') show active @endif" id="aba-resumo" role="tabpanel">
                    <dl class="row">
                        <dt class="col-sm-3">Protocolo</dt>
                        <dd class="col-sm-9">{{ $evento->protocolo }}</dd>

                        <dt class="col-sm-3">Notificante</dt>
                        <dd class="col-sm-9">
                            @if ($evento->anonima)
                                <span class="badge badge-secondary">Anônimo</span>
                            @else
                                {{ $evento->notificante->nome ?? '—' }}
                                @if ($evento->setorNotificante) ({{ $evento->setorNotificante->sigla }}) @endif
                            @endif
                        </dd>

                        <dt class="col-sm-3">Data/hora da ocorrência</dt>
                        <dd class="col-sm-9">
                            {{ $evento->data_ocorrencia->format('d/m/Y') }}
                            @if ($evento->hora_ocorrencia) às {{ substr($evento->hora_ocorrencia, 0, 5) }} @endif
                            · Turno: {{ $evento->turno->label() }}
                        </dd>

                        <dt class="col-sm-3">Setor / local</dt>
                        <dd class="col-sm-9">
                            {{ $evento->setorOcorrencia->nome }}
                            @if ($evento->local_especifico) — {{ $evento->local_especifico }} @endif
                        </dd>

                        <dt class="col-sm-3">Categoria</dt>
                        <dd class="col-sm-9">
                            {{ $evento->categoria->nome_completo }}
                            @if ($evento->categoria->pai) (raiz: {{ $evento->categoria->pai->nome }}) @endif
                        </dd>

                        <dt class="col-sm-3">Descrição</dt>
                        <dd class="col-sm-9">{{ $evento->descricao }}</dd>

                        <dt class="col-sm-3">Fatores contribuintes</dt>
                        <dd class="col-sm-9">{{ $evento->fatores_contribuintes ?? '—' }}</dd>

                        <dt class="col-sm-3">Ação imediata</dt>
                        <dd class="col-sm-9">{{ $evento->acao_imediata ?? '—' }}</dd>
                    </dl>
                </div>

                {{-- ── Envolvidos ── --}}
                <div class="tab-pane fade @if ($abaAtiva === 'envolvidos') show active @endif" id="aba-envolvidos" role="tabpanel">
                    <dl class="row">
                        <dt class="col-sm-3">Paciente</dt>
                        <dd class="col-sm-9">
                            @if ($evento->paciente_envolvido)
                                Código: <code>{{ $evento->paciente_codigo ?? '—' }}</code>
                                · {{ $evento->paciente_faixa_etaria?->label() ?? 'Faixa etária não informada' }}
                                · {{ $evento->paciente_sexo?->label() ?? 'Sexo não informado' }}
                                <small class="d-block text-muted">
                                    <i class="fas fa-shield-alt text-success mr-1"></i>
                                    Identificador pseudonimizado — nome, CPF e prontuário nunca são armazenados (LGPD).
                                </small>
                            @else
                                Sem paciente envolvido
                            @endif
                        </dd>

                        <dt class="col-sm-3">Profissionais</dt>
                        <dd class="col-sm-9">
                            @forelse ($evento->profissionais as $profissional)
                                <span class="badge badge-light border">
                                    {{ $profissional->nome }} · {{ $profissional->categoria_profissional->label() }} · {{ $profissional->papel->label() }}
                                </span>
                            @empty
                                —
                            @endforelse
                        </dd>
                    </dl>
                </div>

                {{-- ── Classificação ── --}}
                <div class="tab-pane fade @if ($abaAtiva === 'classificacao') show active @endif" id="aba-classificacao" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Classificação do incidente</dt>
                                <dd class="col-sm-7"><span class="badge badge-{{ $evento->classificacao->cor() }}">{{ $evento->classificacao->label() }}</span></dd>
                                <dt class="col-sm-5">Grau de dano</dt>
                                <dd class="col-sm-7"><x-badge-dano :dano="$evento->grau_dano" /></dd>
                                <dt class="col-sm-5">Evitabilidade</dt>
                                <dd class="col-sm-7"><span class="badge badge-{{ $evento->evitabilidade->cor() }}">{{ $evento->evitabilidade->label() }}</span></dd>
                                <dt class="col-sm-5">Probabilidade</dt>
                                <dd class="col-sm-7">{{ $evento->probabilidade ?? '—' }}</dd>
                                <dt class="col-sm-5">Severidade</dt>
                                <dd class="col-sm-7">{{ $evento->severidade ?? '—' }}</dd>
                                <dt class="col-sm-5">Score / nível de risco</dt>
                                <dd class="col-sm-7"><x-badge-risco :nivel="$evento->nivel_risco" :score="$evento->score_risco" /></dd>
                                <dt class="col-sm-5">Prazo de conclusão</dt>
                                <dd class="col-sm-7"><x-indicador-prazo :evento="$evento" /></dd>
                                @if ($evento->feedback_notificante)
                                    <dt class="col-sm-5">Devolutiva ao notificante</dt>
                                    <dd class="col-sm-7">{{ $evento->feedback_notificante }}</dd>
                                @endif
                            </dl>
                        </div>
                        <div class="col-md-6">
                            @if ($evento->probabilidade && $evento->severidade)
                                <p class="mb-1 font-weight-bold">Posição na matriz de risco 5×5:</p>
                                <table class="matriz-risco" aria-label="Posição do evento na matriz de risco">
                                    <tbody>
                                        @for ($p = 5; $p >= 1; $p--)
                                            <tr>
                                                <th scope="row" class="small">P{{ $p }}</th>
                                                @for ($s = 1; $s <= 5; $s++)
                                                    @php($score = $p * $s)
                                                    @php($classe = $score <= 4 ? 'baixo' : ($score <= 9 ? 'moderado' : ($score <= 15 ? 'alto' : 'extremo')))
                                                    <td class="risco-{{ $classe }} @if ($p === $evento->probabilidade && $s === $evento->severidade) celula-selecionada @endif">
                                                        {{ $score }}
                                                    </td>
                                                @endfor
                                            </tr>
                                        @endfor
                                        <tr>
                                            <th></th>
                                            @for ($s = 1; $s <= 5; $s++)<th class="small">S{{ $s }}</th>@endfor
                                        </tr>
                                    </tbody>
                                </table>
                            @else
                                <p class="text-muted">Evento ainda não classificado na matriz de risco.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── Investigação ── --}}
                <div class="tab-pane fade @if ($abaAtiva === 'investigacao') show active @endif" id="aba-investigacao" role="tabpanel">
                    @if ($evento->investigacao)
                        <dl class="row">
                            <dt class="col-sm-3">Status</dt>
                            <dd class="col-sm-9"><span class="badge badge-{{ $evento->investigacao->status->cor() }}">{{ $evento->investigacao->status->label() }}</span></dd>
                            <dt class="col-sm-3">Método</dt>
                            <dd class="col-sm-9">{{ $evento->investigacao->metodo->label() }}</dd>
                            <dt class="col-sm-3">Responsável</dt>
                            <dd class="col-sm-9">{{ $evento->investigacao->responsavel->nome }}</dd>
                            <dt class="col-sm-3">Causa raiz</dt>
                            <dd class="col-sm-9">{{ $evento->investigacao->causa_raiz ?? 'Em análise' }}</dd>
                        </dl>
                        @can('investigacao.gerenciar')
                            <a href="{{ route('investigacoes.show', $evento->investigacao) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-search mr-1"></i> Abrir investigação completa
                            </a>
                        @endcan
                    @else
                        <p class="text-muted mb-2">Nenhuma investigação registrada para este evento.</p>
                        @if ($ehGestor && $evento->status === \App\Enums\StatusEvento::EmInvestigacao)
                            @can('investigacao.gerenciar')
                                <a href="{{ route('investigacoes.create', $evento) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-plus mr-1"></i> Abrir investigação
                                </a>
                            @endcan
                        @endif
                    @endif
                </div>

                {{-- ── Ações corretivas ── --}}
                <div class="tab-pane fade @if ($abaAtiva === 'acoes') show active @endif" id="aba-acoes" role="tabpanel">
                    @if ($ehGestor && ! in_array($evento->status, [\App\Enums\StatusEvento::Concluido, \App\Enums\StatusEvento::Cancelado, \App\Enums\StatusEvento::Rascunho], true))
                        @can('acao.gerenciar')
                            <a href="{{ route('acoes.create', $evento) }}" class="btn btn-primary btn-sm mb-3">
                                <i class="fas fa-plus mr-1"></i> Nova ação corretiva
                            </a>
                        @endcan
                    @endif

                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Responsável</th>
                                    <th>Prazo</th>
                                    <th>Status</th>
                                    <th>Eficácia</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($evento->acoesCorretivas as $acao)
                                    <tr>
                                        <td>{{ $acao->titulo }}</td>
                                        <td>{{ $acao->tipo->label() }}</td>
                                        <td>{{ $acao->responsavel->nome }}</td>
                                        <td>{{ $acao->prazo->format('d/m/Y') }}</td>
                                        <td><span class="badge badge-{{ $acao->status->cor() }}">{{ $acao->status->label() }}</span></td>
                                        <td><span class="badge badge-{{ $acao->eficacia->cor() }}">{{ $acao->eficacia->label() }}</span></td>
                                        <td class="text-right">
                                            @can('update', $acao)
                                                <a href="{{ route('acoes.edit', $acao) }}" class="btn btn-outline-primary btn-xs" title="Editar"><i class="fas fa-pen"></i></a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-3">Nenhuma ação corretiva cadastrada.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Anexos ── --}}
                <div class="tab-pane fade @if ($abaAtiva === 'anexos') show active @endif" id="aba-anexos" role="tabpanel">
                    @can('anexar', $evento)
                        <form method="POST" action="{{ route('eventos.anexo.store', $evento) }}" enctype="multipart/form-data" class="form-inline mb-3">
                            @csrf
                            <input type="file" name="anexo" class="form-control-file mr-2" required accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx">
                            <button class="btn btn-primary btn-sm"><i class="fas fa-upload mr-1"></i> Anexar</button>
                            <small class="text-muted ml-2">Máx. 10 MB — PDF, JPG, PNG, DOCX, XLSX</small>
                        </form>
                    @endcan

                    <ul class="list-group">
                        @forelse ($evento->anexos as $anexo)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <i class="far fa-file mr-1"></i>
                                    <a href="{{ route('eventos.anexo.download', [$evento, $anexo]) }}">{{ $anexo->nome_original }}</a>
                                    <small class="text-muted">({{ $anexo->tamanho_legivel }})</small>
                                </span>
                                @can('anexar', $evento)
                                    <form method="POST" action="{{ route('eventos.anexo.destroy', [$evento, $anexo]) }}"
                                          onsubmit="return confirm('Remover este anexo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-xs" aria-label="Remover anexo"><i class="fas fa-times"></i></button>
                                    </form>
                                @endcan
                            </li>
                        @empty
                            <li class="list-group-item text-muted text-center">Nenhum anexo.</li>
                        @endforelse
                    </ul>
                </div>

                {{-- ── Comentários ── --}}
                <div class="tab-pane fade @if ($abaAtiva === 'comentarios') show active @endif" id="aba-comentarios" role="tabpanel">
                    @can('comentar', $evento)
                        <form method="POST" action="{{ route('eventos.comentario.store', $evento) }}" class="mb-3">
                            @csrf
                            <div class="form-group">
                                <label for="corpo" class="sr-only">Comentário</label>
                                <textarea name="corpo" id="corpo" class="form-control" rows="2" maxlength="3000" required
                                          placeholder="Escreva um comentário…"></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                @if ($ehGestor)
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="interno" name="interno" value="1">
                                        <label class="custom-control-label" for="interno">
                                            Interno (invisível ao notificador)
                                        </label>
                                    </div>
                                @else
                                    <span></span>
                                @endif
                                <button class="btn btn-primary btn-sm"><i class="far fa-comment mr-1"></i> Comentar</button>
                            </div>
                        </form>
                    @endcan

                    @forelse ($comentarios as $comentario)
                        <div class="card card-body py-2 mb-2 {{ $comentario->interno ? 'bg-light border-warning' : '' }}">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $comentario->autor->nome }}</strong>
                                <small class="text-muted">
                                    {{ $comentario->created_at->format('d/m/Y H:i') }}
                                    @if ($comentario->interno)
                                        <span class="badge badge-warning ml-1">Interno</span>
                                    @endif
                                </small>
                            </div>
                            <p class="mb-0">{{ $comentario->corpo }}</p>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">Nenhum comentário.</p>
                    @endforelse
                </div>

                {{-- ── Histórico ── --}}
                <div class="tab-pane fade @if ($abaAtiva === 'historico') show active @endif" id="aba-historico" role="tabpanel">
                    <div class="timeline">
                        @foreach ($evento->historico as $registro)
                            <div>
                                <i class="fas fa-circle bg-{{ \App\Enums\StatusEvento::tryFrom($registro->status_novo)?->cor() ?? 'secondary' }}"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="far fa-clock mr-1"></i>{{ $registro->created_at->format('d/m/Y H:i') }}</span>
                                    <h3 class="timeline-header">
                                        {{ \App\Enums\StatusEvento::tryFrom($registro->status_novo)?->label() ?? $registro->status_novo }}
                                        <small class="text-muted">
                                            — {{ str_replace('_', ' ', $registro->acao) }}
                                            @if ($registro->usuario) por {{ $registro->usuario->nome }} @endif
                                        </small>
                                    </h3>
                                    @if ($registro->observacao)
                                        <div class="timeline-body">{{ $registro->observacao }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        <div><i class="far fa-clock bg-gray"></i></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Modal de cancelamento --}}
    @can('cancelar', $evento)
        <div class="modal fade" id="modal-cancelar" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form method="POST" action="{{ route('eventos.cancelar', $evento) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Cancelar evento {{ $evento->protocolo }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-0">
                            <label for="justificativa-cancelar">
                                Justificativa @if ($evento->status !== \App\Enums\StatusEvento::Rascunho) <span class="text-danger">*</span> @endif
                            </label>
                            <textarea name="justificativa" id="justificativa-cancelar" class="form-control" rows="3"
                                      @if ($evento->status !== \App\Enums\StatusEvento::Rascunho) required @endif
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
    @endcan
@endsection
