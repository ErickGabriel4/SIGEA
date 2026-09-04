{{-- T06 — Minhas notificações (notificador) --}}
@extends('layouts.app')

@section('title', 'Minhas Notificações · SIGEA')
@section('titulo', 'Minhas Notificações')
@section('breadcrumb')
    <li class="breadcrumb-item active">Minhas notificações</li>
@endsection

@section('conteudo')
    <div class="mb-3">
        <a href="{{ route('eventos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle mr-1"></i> Notificar novo evento
        </a>
    </div>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Protocolo</th>
                        <th>Data da ocorrência</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Devolutiva do NSP</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($eventos as $evento)
                        <tr>
                            <td><a href="{{ route('eventos.show', $evento) }}">{{ $evento->protocolo }}</a></td>
                            <td>{{ $evento->data_ocorrencia->format('d/m/Y') }}</td>
                            <td>{{ $evento->categoria->pai->nome ?? $evento->categoria->nome }}</td>
                            <td><x-badge-status :status="$evento->status" /></td>
                            <td>
                                @if ($evento->feedback_notificante)
                                    <span class="text-success"><i class="fas fa-comment-dots mr-1"></i>Recebida</span>
                                @else
                                    <span class="text-muted">Aguardando</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if ($evento->status === \App\Enums\StatusEvento::Rascunho)
                                    <a href="{{ route('eventos.edit', $evento) }}" class="btn btn-outline-primary btn-sm"
                                       title="Continuar preenchimento do rascunho">
                                        <i class="fas fa-pen"></i> Continuar
                                    </a>
                                @else
                                    <a href="{{ route('eventos.show', $evento) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Você ainda não registrou nenhuma notificação.
                                <br>
                                <a href="{{ route('eventos.create') }}" class="btn btn-primary btn-sm mt-2">Notificar meu primeiro evento</a>
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
