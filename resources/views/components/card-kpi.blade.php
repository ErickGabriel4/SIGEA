@props(['titulo', 'valor', 'icone' => 'fas fa-chart-bar', 'cor' => 'info', 'rodape' => null])
{{-- Card KPI do painel (small-box AdminLTE) --}}
<div class="small-box bg-{{ $cor }}">
    <div class="inner">
        <h3>{{ $valor }}</h3>
        <p>{{ $titulo }}</p>
    </div>
    <div class="icon">
        <i class="{{ $icone }}" aria-hidden="true"></i>
    </div>
    @if ($rodape)
        <div class="small-box-footer">{{ $rodape }}</div>
    @endif
</div>
