{{-- Relatório por setor --}}
@extends('layouts.app')

@section('title', 'Relatório por Setor · SIGEA')
@section('titulo', 'Relatório por Setor')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('relatorios.index') }}">Relatórios</a></li>
    <li class="breadcrumb-item active">Por setor</li>
@endsection

@section('conteudo')
    @include('relatorios.partials.filtros', ['tipo' => 'por-setor', 'setores' => null])

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>Setor</th>
                        <th>Tipo</th>
                        <th class="text-right">Leitos</th>
                        <th class="text-right">Eventos</th>
                        <th class="text-right">Com dano</th>
                        <th class="text-right">Graves/óbito</th>
                        <th class="text-right">Concluídos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($linhas as $setor)
                        <tr>
                            <td>{{ $setor->nome }} ({{ $setor->sigla }})</td>
                            <td>{{ $setor->tipo->label() }}</td>
                            <td class="text-right">{{ $setor->leitos ?? '—' }}</td>
                            <td class="text-right">{{ $setor->total }}</td>
                            <td class="text-right">{{ $setor->com_dano }}</td>
                            <td class="text-right">{{ $setor->graves }}</td>
                            <td class="text-right">{{ $setor->concluidos }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
