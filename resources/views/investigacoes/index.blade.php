{{-- T11 — Lista de investigações --}}
@extends('layouts.app')

@section('title', 'Investigações · SIGEA')
@section('titulo', 'Investigações de Causa Raiz')
@section('breadcrumb')
    <li class="breadcrumb-item active">Investigações</li>
@endsection

@section('conteudo')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('investigacoes.index') }}" class="form-row">
                <div class="form-group col-md-3 col-6">
                    <label for="f-status">Status</label>
                    <select id="f-status" name="status" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach (\App\Enums\StatusInvestigacao::cases() as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3 col-6">
                    <label for="f-metodo">Método</label>
                    <select id="f-metodo" name="metodo" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach (\App\Enums\MetodoInvestigacao::cases() as $metodo)
                            <option value="{{ $metodo->value }}" @selected(request('metodo') === $metodo->value)>{{ $metodo->label() }}</option>
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
                <div class="form-group col-md-3 col-6 d-flex align-items-end">
                    <div class="custom-control custom-checkbox mb-2 mr-2">
                        <input type="checkbox" class="custom-control-input" id="f-atrasadas" name="atrasadas" value="1" @checked(request('atrasadas'))>
                        <label class="custom-control-label" for="f-atrasadas">Somente atrasadas</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mb-2">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Categoria</th>
                        <th>Método</th>
                        <th>Responsável</th>
                        <th>Início</th>
                        <th>Prevista</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($investigacoes as $investigacao)
                        <tr class="{{ $investigacao->atrasada ? 'table-danger' : '' }}">
                            <td><a href="{{ route('investigacoes.show', $investigacao) }}">{{ $investigacao->evento->protocolo }}</a></td>
                            <td>{{ $investigacao->evento->categoria->pai->nome ?? $investigacao->evento->categoria->nome }}</td>
                            <td>{{ $investigacao->metodo->label() }}</td>
                            <td>{{ $investigacao->responsavel->nome }}</td>
                            <td>{{ $investigacao->data_inicio->format('d/m/Y') }}</td>
                            <td>
                                {{ $investigacao->data_prevista?->format('d/m/Y') ?? '—' }}
                                @if ($investigacao->atrasada)
                                    <span class="badge badge-danger ml-1">Atrasada</span>
                                @endif
                            </td>
                            <td><span class="badge badge-{{ $investigacao->status->cor() }}">{{ $investigacao->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma investigação encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $investigacoes->links() }}
        </div>
    </div>
@endsection
