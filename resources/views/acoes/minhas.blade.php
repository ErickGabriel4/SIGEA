{{-- Minhas ações (responsável); conclusão exige evidência --}}
@extends('layouts.app')

@section('title', 'Minhas Ações · SIGEA')
@section('titulo', 'Minhas Ações Corretivas')
@section('breadcrumb')
    <li class="breadcrumb-item active">Minhas ações</li>
@endsection

@section('conteudo')
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Ação</th>
                        <th>Evento</th>
                        <th>Tipo</th>
                        <th>Prazo</th>
                        <th>Status</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($acoes as $acao)
                        <tr class="{{ $acao->status === \App\Enums\StatusAcao::Atrasada ? 'table-danger' : '' }}">
                            <td>
                                {{ $acao->titulo }}
                                <div class="small text-muted">{{ \Illuminate\Support\Str::limit($acao->descricao, 90) }}</div>
                            </td>
                            <td>{{ $acao->evento->protocolo }}</td>
                            <td>{{ $acao->tipo->label() }}</td>
                            <td>{{ $acao->prazo->format('d/m/Y') }}</td>
                            <td><span class="badge badge-{{ $acao->status->cor() }}">{{ $acao->status->label() }}</span></td>
                            <td class="text-right">
                                @can('concluir', $acao)
                                    <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modal-concluir-{{ $acao->id }}">
                                        <i class="fas fa-check mr-1"></i> Concluir
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Você não possui ações atribuídas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $acoes->links() }}
        </div>
    </div>

    {{-- Modais de conclusão com evidência obrigatória --}}
    @foreach ($acoes as $acao)
        @can('concluir', $acao)
            <div class="modal fade" id="modal-concluir-{{ $acao->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <form method="POST" action="{{ route('acoes.concluir', $acao) }}" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Concluir — {{ $acao->titulo }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group mb-0">
                                <label for="evidencia-{{ $acao->id }}">
                                    Evidência de execução <span class="text-danger">*</span>
                                </label>
                                <textarea name="evidencia_conclusao" id="evidencia-{{ $acao->id }}" class="form-control"
                                          rows="3" minlength="10" required
                                          placeholder="Como a execução pode ser comprovada?"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Voltar</button>
                            <button type="submit" class="btn btn-success">Confirmar conclusão</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @endforeach
@endsection
