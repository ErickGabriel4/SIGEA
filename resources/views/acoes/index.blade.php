{{-- Lista geral de ações corretivas --}}
@extends('layouts.app')

@section('title', 'Ações Corretivas · SIGEA')
@section('titulo', 'Ações Corretivas')
@section('breadcrumb')
    <li class="breadcrumb-item active">Ações corretivas</li>
@endsection

@section('conteudo')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('acoes.index') }}" class="form-row">
                <div class="form-group col-md-3 col-6">
                    <label for="f-status">Status</label>
                    <select id="f-status" name="status" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach (\App\Enums\StatusAcao::cases() as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3 col-6">
                    <label for="f-responsavel">Responsável</label>
                    <select id="f-responsavel" name="responsavel_id" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach ($responsaveis as $responsavel)
                            <option value="{{ $responsavel->id }}" @selected(request('responsavel_id') == $responsavel->id)>{{ $responsavel->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3 col-6">
                    <label for="f-setor">Setor</label>
                    <select id="f-setor" name="setor_id" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach ($setores as $setor)
                            <option value="{{ $setor->id }}" @selected(request('setor_id') == $setor->id)>{{ $setor->sigla }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 col-6">
                    <label for="f-prazo">Prazo até</label>
                    <input type="date" id="f-prazo" name="prazo_ate" value="{{ request('prazo_ate') }}" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm btn-block">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Ação</th>
                        <th>Evento</th>
                        <th>Tipo</th>
                        <th>Responsável</th>
                        <th>Setor</th>
                        <th>Prazo</th>
                        <th>Status</th>
                        <th>Eficácia</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($acoes as $acao)
                        <tr class="{{ $acao->status === \App\Enums\StatusAcao::Atrasada ? 'table-danger' : '' }}">
                            <td>{{ $acao->titulo }}</td>
                            <td><a href="{{ route('eventos.show', ['evento' => $acao->evento_adverso_id, 'aba' => 'acoes']) }}">{{ $acao->evento->protocolo }}</a></td>
                            <td>{{ $acao->tipo->label() }}</td>
                            <td>{{ $acao->responsavel->nome }}</td>
                            <td>{{ $acao->setor->sigla ?? '—' }}</td>
                            <td>{{ $acao->prazo->format('d/m/Y') }}</td>
                            <td><span class="badge badge-{{ $acao->status->cor() }}">{{ $acao->status->label() }}</span></td>
                            <td><span class="badge badge-{{ $acao->eficacia->cor() }}">{{ $acao->eficacia->label() }}</span></td>
                            <td class="text-right text-nowrap">
                                @can('update', $acao)
                                    <a href="{{ route('acoes.edit', $acao) }}" class="btn btn-outline-primary btn-sm" title="Editar"><i class="fas fa-pen"></i></a>
                                @endcan
                                @can('avaliar', $acao)
                                    <button class="btn btn-outline-success btn-sm" title="Avaliar eficácia"
                                            data-toggle="modal" data-target="#modal-avaliar-{{ $acao->id }}">
                                        <i class="fas fa-star-half-alt"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">Nenhuma ação encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $acoes->links() }}
        </div>
    </div>

    {{-- Modais de avaliação de eficácia --}}
    @foreach ($acoes as $acao)
        @can('avaliar', $acao)
            <div class="modal fade" id="modal-avaliar-{{ $acao->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <form method="POST" action="{{ route('acoes.avaliar', $acao) }}" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Avaliar eficácia — {{ $acao->titulo }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted">Evidência registrada: {{ $acao->evidencia_conclusao ?? '—' }}</p>
                            <div class="form-group mb-0">
                                <label for="eficacia-{{ $acao->id }}">A ação resolveu o problema?</label>
                                <select name="eficacia" id="eficacia-{{ $acao->id }}" class="form-control" required>
                                    <option value="eficaz">Eficaz — problema resolvido</option>
                                    <option value="parcialmente_eficaz">Parcialmente eficaz</option>
                                    <option value="ineficaz">Ineficaz — problema persiste</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Voltar</button>
                            <button type="submit" class="btn btn-success">Registrar avaliação</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @endforeach
@endsection
