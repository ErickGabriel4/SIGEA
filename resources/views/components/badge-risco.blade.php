@props(['nivel', 'score' => null])
{{-- Badge do nível de risco da matriz 5×5 --}}
@if ($nivel !== null)
    <span {{ $attributes->merge(['class' => 'badge badge-' . $nivel->cor()]) }}>
        {{ $nivel->label() }}@if ($score !== null) ({{ $score }})@endif
    </span>
@else
    <span class="badge badge-light">Não avaliado</span>
@endif
