{{-- T09 — Fila de triagem priorizada por tempo de espera (UC06) --}}
@extends('layouts.app')

@section('title', 'Fila de Triagem · SIGEA')
@section('titulo', 'Fila de Triagem')
@section('breadcrumb')
    <li class="breadcrumb-item active">Triagem</li>
@endsection

@section('conteudo')
    <div class="callout callout-info">
        <i class="fas fa-stopwatch mr-1"></i>
        SLA de triagem: <strong>{{ $slaHoras }} horas</strong>. Eventos destacados em vermelho ultrapassaram o prazo.
    </div>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Protocolo</th>
                        <th>Notificado em</th>
                        <th>Espera</th>
                        <th>Categoria</th>
                        <th>Setor</th>
                        <th>Anônima</th>
                        <th class="text-right">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($eventos as $evento)
                        @php($estourado = $evento->created_at->lt($limiteSla))
                        <tr class="{{ $estourado ? 'table-danger' : '' }}">
                            <td>
                                <a href="{{ route('eventos.show', $evento) }}">{{ $evento->protocolo }}</a>
                                @if ($estourado)
                                    <span class="badge badge-danger ml-1" title="SLA de triagem excedido">SLA excedido</span>
                                @endif
                            </td>
                            <td>{{ $evento->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $evento->created_at->diffForHumans(null, true) }}</td>
                            <td>{{ $evento->categoria->pai->nome ?? $evento->categoria->nome }}</td>
                            <td>{{ $evento->setorOcorrencia->sigla }}</td>
                            <td>{{ $evento->anonima ? 'Sim' : 'Não' }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('triagem.assumir', $evento) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-primary btn-sm">
                                        <i class="fas fa-hand-paper mr-1"></i> Assumir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-check-circle text-success mr-1"></i>
                                Fila vazia — nenhum evento aguardando triagem.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $eventos->links() }}
        </div>
    </div>
@endsection
