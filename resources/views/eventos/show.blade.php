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
                @foreach (['resumo' => 'Resumo', 'envolvidos' => 'Envolvidos', 'historico' => 'Histórico'] as $aba => $rotulo)
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
