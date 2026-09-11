{{-- Relatório de ações pendentes/atrasadas --}}
@extends('layouts.app')

@section('title', 'Ações Pendentes · SIGEA')
@section('titulo', 'Relatório de Ações Pendentes')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('relatorios.index') }}">Relatórios</a></li>
    <li class="breadcrumb-item active">Ações pendentes</li>
@endsection

@section('conteudo')
    @include('relatorios.partials.filtros', ['tipo' => 'acoes-pendentes', 'setores' => \App\Models\Setor::ativos()->orderBy('nome')->get()])

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>Ação</th>
                        <th>Evento</th>
                        <th>Responsável</th>
                        <th>Setor</th>
                        <th>Prazo</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($acoes as $acao)
                        <tr class="{{ $acao->status === \App\Enums\StatusAcao::Atrasada ? 'table-danger' : '' }}">
                            <td>{{ $acao->titulo }}</td>
                            <td>{{ $acao->evento->protocolo }}</td>
                            <td>{{ $acao->responsavel->nome }}</td>
                            <td>{{ $acao->setor->sigla ?? '—' }}</td>
                            <td>{{ $acao->prazo->format('d/m/Y') }}</td>
                            <td><span class="badge badge-{{ $acao->status->cor() }}">{{ $acao->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma ação pendente. 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
