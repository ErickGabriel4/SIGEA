@props(['prioridade'])
{{-- Badge da prioridade de tratativa --}}
<span {{ $attributes->merge(['class' => 'badge badge-' . $prioridade->cor()]) }}>{{ $prioridade->label() }}</span>
