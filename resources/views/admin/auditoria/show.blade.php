{{-- T21 — Diff da alteração (valores anteriores × novos) --}}
@extends('layouts.app')

@section('title', 'Auditoria #' . $registro->id . ' · SIGEA')
@section('titulo', 'Registro de Auditoria #' . $registro->id)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.auditoria.index') }}">Auditoria</a></li>
    <li class="breadcrumb-item active">#{{ $registro->id }}</li>
@endsection

@section('conteudo')
    @php($antigos = $registro->properties['old'] ?? [])
    @php($novos = $registro->properties['attributes'] ?? [])

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-md-2">Data/hora</dt>
                <dd class="col-md-10">{{ $registro->created_at->format('d/m/Y H:i:s') }}</dd>
                <dt class="col-md-2">Usuário</dt>
                <dd class="col-md-10">{{ $registro->causer->nome ?? 'Sistema / anônimo' }}</dd>
                <dt class="col-md-2">Entidade</dt>
                <dd class="col-md-10">{{ $registro->log_name }} — registro #{{ $registro->subject_id }}</dd>
                <dt class="col-md-2">Operação</dt>
                <dd class="col-md-10">{{ ['created' => 'Criação', 'updated' => 'Alteração', 'deleted' => 'Exclusão'][$registro->event] ?? $registro->description }}</dd>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Comparação de valores</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th style="width:25%">Campo</th>
                        <th style="width:37%">Valor anterior</th>
                        <th style="width:38%">Valor novo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($novos as $campo => $valorNovo)
                        @php($valorAntigo = $antigos[$campo] ?? null)
                        <tr class="{{ $valorAntigo != $valorNovo ? 'table-warning' : '' }}">
                            <td><code>{{ $campo }}</code></td>
                            <td class="text-danger">{{ is_scalar($valorAntigo) ? $valorAntigo : json_encode($valorAntigo, JSON_UNESCAPED_UNICODE) ?? '—' }}</td>
                            <td class="text-success">{{ is_scalar($valorNovo) ? $valorNovo : json_encode($valorNovo, JSON_UNESCAPED_UNICODE) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">Sem detalhes de alteração.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
