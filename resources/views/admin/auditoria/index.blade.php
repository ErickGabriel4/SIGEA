{{-- T21 — Admin: trilha de auditoria --}}
@extends('layouts.app')

@section('title', 'Auditoria · SIGEA')
@section('titulo', 'Trilha de Auditoria')
@section('breadcrumb')
    <li class="breadcrumb-item active">Auditoria</li>
@endsection

@section('conteudo')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.auditoria.index') }}" class="form-row align-items-end">
                <div class="form-group col-md-3 col-6">
                    <label for="a-usuario">Usuário</label>
                    <select id="a-usuario" name="usuario_id" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach ($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" @selected(request('usuario_id') == $usuario->id)>{{ $usuario->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3 col-6">
                    <label for="a-log">Entidade</label>
                    <select id="a-log" name="log" class="form-control form-control-sm">
                        <option value="">Todas</option>
                        @foreach ($logs as $log)
                            <option value="{{ $log }}" @selected(request('log') === $log)>{{ $log }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 col-6">
                    <label for="a-inicio">Início</label>
                    <input type="date" id="a-inicio" name="inicio" value="{{ request('inicio') }}" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-2 col-6">
                    <label for="a-fim">Fim</label>
                    <input type="date" id="a-fim" name="fim" value="{{ request('fim') }}" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-2">
                    <button class="btn btn-primary btn-sm btn-block">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>Data/hora</th>
                        <th>Usuário</th>
                        <th>Entidade</th>
                        <th>Operação</th>
                        <th>Registro</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $registro)
                        <tr>
                            <td>{{ $registro->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $registro->causer->nome ?? 'Sistema / anônimo' }}</td>
                            <td><span class="badge badge-light border">{{ $registro->log_name }}</span></td>
                            <td>{{ ['created' => 'Criação', 'updated' => 'Alteração', 'deleted' => 'Exclusão'][$registro->event] ?? $registro->description }}</td>
                            <td>#{{ $registro->subject_id }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.auditoria.show', $registro) }}" class="btn btn-outline-primary btn-sm" title="Ver diff">
                                    <i class="fas fa-exchange-alt"></i> Diff
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum registro de auditoria.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $registros->links() }}
        </div>
    </div>
@endsection
