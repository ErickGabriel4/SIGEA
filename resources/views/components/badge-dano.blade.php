@props(['dano'])
{{-- Badge do grau de dano (escala WHO/ICPS) --}}
<span {{ $attributes->merge(['class' => 'badge badge-' . $dano->cor()]) }}>{{ $dano->label() }}</span>
