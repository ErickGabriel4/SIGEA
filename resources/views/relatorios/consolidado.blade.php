{{-- Relatório consolidado (US28) --}}
@extends('layouts.app')

@section('title', 'Relatório Consolidado · SIGEA')
@section('titulo', 'Relatório Consolidado')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('relatorios.index') }}">Relatórios</a></li>
    <li class="breadcrumb-item active">Consolidado</li>
@endsection

@section('conteudo')
    @include('relatorios.partials.filtros', ['tipo' => 'consolidado', 'setores' => null])

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Indicadores do período</h3></div>
                <div class="card-body">
                    @include('relatorios.partials.tabela-indicadores')
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Evolução mensal</h3></div>
                <div class="card-body"><canvas id="rel-serie" height="120"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Por grau de dano</h3></div>
                <div class="card-body"><canvas id="rel-dano" height="220"></canvas></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Top categorias</h3></div>
                <div class="card-body"><canvas id="rel-categoria" height="220"></canvas></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Por setor</h3></div>
                <div class="card-body"><canvas id="rel-setor" height="220"></canvas></div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cores = window.SigeaCores;
    const serie = @json($serieTemporal);
    const dano = @json($porGrauDano);
    const categoria = @json($porCategoria);
    const setor = @json($porSetor);

    new Chart(document.getElementById('rel-serie'), {
        type: 'line',
        data: { labels: serie.rotulos, datasets: [{ label: 'Eventos', data: serie.valores, borderColor: cores.primaria, backgroundColor: 'rgba(0,123,255,.12)', fill: true, tension: .3 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
    });
    new Chart(document.getElementById('rel-dano'), {
        type: 'doughnut',
        data: { labels: dano.rotulos, datasets: [{ data: dano.valores, backgroundColor: [cores.sucesso, cores.primaria, cores.aviso, cores.perigo, cores.escuro] }] },
        options: { plugins: { legend: { position: 'bottom' } } },
    });
    new Chart(document.getElementById('rel-categoria'), {
        type: 'bar',
        data: { labels: categoria.rotulos, datasets: [{ data: categoria.valores, backgroundColor: cores.primaria }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } },
    });
    new Chart(document.getElementById('rel-setor'), {
        type: 'bar',
        data: { labels: setor.rotulos, datasets: [{ data: setor.valores, backgroundColor: cores.serie }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
    });
});
</script>
@endpush
