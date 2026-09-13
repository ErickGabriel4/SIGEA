{{-- T22 — Notificações in-app --}}
@extends('layouts.app')

@section('title', 'Notificações · SIGEA')
@section('titulo', 'Notificações')
@section('breadcrumb')
    <li class="breadcrumb-item active">Notificações</li>
@endsection

@section('conteudo')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Suas notificações</h3>
            <form method="POST" action="{{ route('notificacoes.todas') }}">
                @csrf
                <button class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-check-double mr-1"></i> Marcar todas como lidas
                </button>
            </form>
        </div>
        <div class="card-body p-0">
            <ul class="list-group list-group-flush">
                @forelse ($notificacoes as $notificacao)
                    <li class="list-group-item d-flex justify-content-between align-items-center {{ $notificacao->read_at === null ? 'notificacao-nao-lida' : '' }}">
                        <div>
                            <strong>{{ $notificacao->data['titulo'] ?? 'Notificação' }}</strong>
                            @if ($notificacao->read_at === null)
                                <span class="badge badge-primary ml-1">Nova</span>
                            @endif
                            <div class="small">{{ $notificacao->data['mensagem'] ?? '' }}</div>
                            <div class="small text-muted">{{ $notificacao->created_at->diffForHumans() }}</div>
                        </div>
                        <form method="POST" action="{{ route('notificacoes.lida', $notificacao->id) }}">
                            @csrf
                            <button class="btn btn-outline-secondary btn-sm" title="Abrir e marcar como lida">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="list-group-item text-center text-muted py-4">Nenhuma notificação.</li>
                @endforelse
            </ul>
        </div>
        <div class="card-footer">
            {{ $notificacoes->links() }}
        </div>
    </div>
@endsection
