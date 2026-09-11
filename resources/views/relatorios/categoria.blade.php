{{-- Relatório por categoria (taxonomia ICPS) --}}
@extends('layouts.app')

@section('title', 'Relatório por Categoria · SIGEA')
@section('titulo', 'Relatório por Categoria')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('relatorios.index') }}">Relatórios</a></li>
    <li class="breadcrumb-item active">Por categoria</li>
@endsection

@section('conteudo')
    @include('relatorios.partials.filtros', ['tipo' => 'por-categoria', 'setores' => null])

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Categoria (ICPS)</th>
                        <th class="text-right">Eventos</th>
                        <th class="text-right">Com dano</th>
                        <th class="text-right">Evitáveis</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($linhas as $categoria)
                        <tr>
                            <td>{{ $categoria->codigo }}</td>
                            <td>{{ $categoria->nome }}</td>
                            <td class="text-right">{{ $categoria->total }}</td>
                            <td class="text-right">{{ $categoria->com_dano }}</td>
                            <td class="text-right">{{ $categoria->evitaveis }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
