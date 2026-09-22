{{-- T12 — Investigação: 5 Porquês / Ishikawa com diagrama SVG (UC09/UC10) --}}
@extends('layouts.app')

@php($evento = $investigacao->evento)
@php($editavel = ! in_array($investigacao->status, [\App\Enums\StatusInvestigacao::Concluida, \App\Enums\StatusInvestigacao::Cancelada], true))
@php($metodoCincoPorques = $investigacao->metodo === \App\Enums\MetodoInvestigacao::CincoPorques)

@section('title', 'Investigação ' . $evento->protocolo . ' · SIGEA')
@section('titulo', 'Investigação — ' . $evento->protocolo)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('investigacoes.index') }}">Investigações</a></li>
    <li class="breadcrumb-item active">{{ $evento->protocolo }}</li>
@endsection

@section('conteudo')
    {{-- Cabeçalho com dados do evento --}}
    <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="h6 mb-1">
                    <a href="{{ route('eventos.show', $evento) }}">{{ $evento->protocolo }}</a>
                    — {{ $evento->categoria->nome_completo }}
                </h2>
                <div>
                    <span class="badge badge-{{ $investigacao->status->cor() }}">{{ $investigacao->status->label() }}</span>
                    <span class="badge badge-light border">{{ $investigacao->metodo->label() }}</span>
                    <span class="badge badge-light border">Responsável: {{ $investigacao->responsavel->nome }}</span>
                    <span class="badge badge-light border">
                        Início {{ $investigacao->data_inicio->format('d/m/Y') }}
                        @if ($investigacao->data_prevista) · previsão {{ $investigacao->data_prevista->format('d/m/Y') }} @endif
                    </span>
                    @if ($investigacao->atrasada)
                        <span class="badge badge-danger">Atrasada</span>
                    @endif
                </div>
            </div>
            <div class="mt-2 mt-md-0">
                @if ($editavel)
                    <a href="{{ route('investigacoes.edit', $investigacao) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-pen mr-1"></i> Editar dados
                    </a>
                    <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modal-concluir"
                            @disabled(! $investigacao->podeConcluir())
                            title="{{ $investigacao->podeConcluir() ? '' : 'Marque ao menos uma causa raiz e cadastre ao menos uma ação corretiva para concluir.' }}">
                        <i class="fas fa-flag-checkered mr-1"></i> Concluir investigação
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Cadastro de causas --}}
        <div class="col-lg-5">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ $metodoCincoPorques ? 'Cadeia dos porquês' : 'Causas por eixo (Ishikawa)' }}
                    </h3>
                </div>
                <div class="card-body">
                    @if ($editavel)
                        <form method="POST" action="{{ route('causas.store', $investigacao) }}" class="mb-3">
                            @csrf
                            @if ($metodoCincoPorques)
                                <input type="hidden" name="categoria" value="processo">
                                <div class="form-group">
                                    <label for="nova-causa">
                                        Por quê nº {{ $investigacao->causas->count() + 1 }}?
                                    </label>
                                    <textarea name="descricao" id="nova-causa" class="form-control" rows="2" required
                                              placeholder="{{ $investigacao->causas->isEmpty() ? 'Por que o evento aconteceu?' : 'Por que isso aconteceu?' }}"></textarea>
                                </div>
                            @else
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="categoria-causa">Eixo</label>
                                        <select name="categoria" id="categoria-causa" class="form-control form-control-sm" required>
                                            @foreach (\App\Enums\CategoriaCausa::cases() as $categoria)
                                                <option value="{{ $categoria->value }}">{{ $categoria->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <label for="nova-causa">Causa</label>
                                        <textarea name="descricao" id="nova-causa" class="form-control form-control-sm" rows="2" required
                                                  placeholder="Descreva a causa identificada"></textarea>
                                    </div>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="nova-raiz" name="eh_causa_raiz" value="1">
                                    <label class="custom-control-label" for="nova-raiz">É causa raiz</label>
                                </div>
                                <button class="btn btn-warning btn-sm"><i class="fas fa-plus mr-1"></i> Adicionar causa</button>
                            </div>
                        </form>
                        <hr>
                    @endif

                    @php($causasOrdenadas = $metodoCincoPorques ? $investigacao->causas->sortBy('ordem') : $investigacao->causas->groupBy(fn ($c) => $c->categoria->value))

                    @if ($investigacao->causas->isEmpty())
                        <p class="text-muted text-center mb-0">Nenhuma causa registrada ainda.</p>
                    @elseif ($metodoCincoPorques)
                        <ol class="pl-3">
                            @foreach ($causasOrdenadas as $causa)
                                <li class="mb-2">
                                    {{ $causa->descricao }}
                                    @if ($causa->eh_causa_raiz)
                                        <span class="badge badge-danger ml-1">★ Causa raiz</span>
                                    @endif
                                    @if ($editavel)
                                        @include('investigacoes.partials.botoes-causa', ['causa' => $causa])
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @else
                        @foreach (\App\Enums\CategoriaCausa::cases() as $categoria)
                            @php($causasEixo = $causasOrdenadas[$categoria->value] ?? collect())
                            <p class="mb-1 mt-2"><strong>{{ $categoria->label() }}</strong> <span class="text-muted">({{ $causasEixo->count() }})</span></p>
                            <ul class="pl-3 mb-0">
                                @forelse ($causasEixo as $causa)
                                    <li class="mb-1">
                                        {{ $causa->descricao }}
                                        @if ($causa->eh_causa_raiz)
                                            <span class="badge badge-danger ml-1">★ Causa raiz</span>
                                        @endif
                                        @if ($editavel)
                                            @include('investigacoes.partials.botoes-causa', ['causa' => $causa])
                                        @endif
                                    </li>
                                @empty
                                    <li class="text-muted small">—</li>
                                @endforelse
                            </ul>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- Diagrama + conclusão + ações --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Diagrama de causa e efeito (espinha de peixe)</h3></div>
                <div class="card-body diagrama-ishikawa">
                    <svg id="svg-ishikawa" width="100%" height="440" role="img"
                         aria-label="Diagrama de Ishikawa gerado a partir das causas cadastradas"></svg>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Análise e conclusão</h3></div>
                <div class="card-body">
                    <dl>
                        <dt>Desenvolvimento da análise</dt>
                        <dd>{{ $investigacao->descricao_analise ?? '—' }}</dd>
                        <dt>Causa raiz (síntese)</dt>
                        <dd>{{ $investigacao->causa_raiz ?? 'Pendente' }}</dd>
                        <dt>Conclusão e recomendações</dt>
                        <dd class="mb-0">{{ $investigacao->conclusao ?? 'Pendente' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Ações corretivas decorrentes</h3>
                    @can('acao.gerenciar')
                        <a href="{{ route('acoes.create', $evento) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> Nova ação
                        </a>
                    @endcan
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr><th>Título</th><th>Responsável</th><th>Prazo</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($investigacao->acoesCorretivas as $acao)
                                <tr>
                                    <td>{{ $acao->titulo }}</td>
                                    <td>{{ $acao->responsavel->nome }}</td>
                                    <td>{{ $acao->prazo->format('d/m/Y') }}</td>
                                    <td><span class="badge badge-{{ $acao->status->cor() }}">{{ $acao->status->label() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Nenhuma ação cadastrada — obrigatória para concluir a investigação.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de conclusão da investigação --}}
    <div class="modal fade" id="modal-concluir" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="POST" action="{{ route('investigacoes.concluir', $investigacao) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Concluir investigação — {{ $evento->protocolo }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        Ao concluir, o evento passa para <strong>aguardando ação</strong> e as ações corretivas
                        cadastradas seguem para execução.
                    </p>
                    <div class="form-group">
                        <label for="causa_raiz">Síntese da causa raiz <span class="text-danger">*</span></label>
                        <textarea name="causa_raiz" id="causa_raiz" class="form-control" rows="2" required>{{ old('causa_raiz', $investigacao->causa_raiz) }}</textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label for="conclusao">Conclusão e recomendações <span class="text-danger">*</span></label>
                        <textarea name="conclusao" id="conclusao" class="form-control" rows="4" required>{{ old('conclusao', $investigacao->conclusao) }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Voltar</button>
                    <button type="submit" class="btn btn-success">Concluir investigação</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const causas = @json($investigacao->causas->groupBy(fn ($c) => $c->categoria->value)->map(fn ($grupo) => $grupo->map(fn ($c) => ['descricao' => $c->descricao, 'raiz' => (bool) $c->eh_causa_raiz])->values()));
    window.desenharIshikawa('svg-ishikawa', causas, @json(($evento->categoria->pai->nome ?? $evento->categoria->nome)));
});
</script>
@endpush
