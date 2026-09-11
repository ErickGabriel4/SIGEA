{{-- T16 — Menu de relatórios em cards --}}
@extends('layouts.app')

@section('title', 'Relatórios · SIGEA')
@section('titulo', 'Relatórios')
@section('breadcrumb')
    <li class="breadcrumb-item active">Relatórios</li>
@endsection

@section('conteudo')
    <div class="row">
        @foreach ([
            ['rota' => 'relatorios.consolidado', 'icone' => 'fas fa-chart-pie', 'cor' => 'primary', 'titulo' => 'Consolidado', 'descricao' => 'Indicadores gerais, evolução mensal e distribuições do período.'],
            ['rota' => 'relatorios.setor', 'icone' => 'fas fa-hospital', 'cor' => 'info', 'titulo' => 'Por setor', 'descricao' => 'Volume, eventos com dano e conclusão por setor hospitalar.'],
            ['rota' => 'relatorios.categoria', 'icone' => 'fas fa-sitemap', 'cor' => 'warning', 'titulo' => 'Por categoria', 'descricao' => 'Distribuição pela taxonomia ICPS e evitabilidade por tipo.'],
            ['rota' => 'relatorios.acoes', 'icone' => 'fas fa-clipboard-check', 'cor' => 'danger', 'titulo' => 'Ações pendentes', 'descricao' => 'Ações corretivas abertas e atrasadas com responsáveis.'],
        ] as $item)
            <div class="col-md-3 col-sm-6">
                <div class="card card-outline card-{{ $item['cor'] }} h-100">
                    <div class="card-body text-center">
                        <i class="{{ $item['icone'] }} fa-2x text-{{ $item['cor'] }} mb-2" aria-hidden="true"></i>
                        <h3 class="h6">{{ $item['titulo'] }}</h3>
                        <p class="small text-muted">{{ $item['descricao'] }}</p>
                    </div>
                    <div class="card-footer text-center">
                        <a href="{{ route($item['rota']) }}" class="btn btn-{{ $item['cor'] }} btn-sm">Abrir</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
