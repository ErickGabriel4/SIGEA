@props(['status'])
{{-- Badge de status do evento: cor + texto (nunca apenas cor — WCAG) --}}
<span {{ $attributes->merge(['class' => 'badge badge-' . $status->cor()]) }}>{{ $status->label() }}</span>
