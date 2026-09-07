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
@endsection
