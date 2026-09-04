@props(['evento'])
{{-- Indicador de prazo: verde (no prazo) / amarelo (próximo) / vermelho (estourado) --}}
@php($situacao = $evento->situacao_prazo)
@if ($situacao !== null)
    <span class="prazo-indicador prazo-{{ $situacao }}"
          title="Prazo de conclusão: {{ $evento->prazo_conclusao->format('d/m/Y') }}">
        <span class="ponto" aria-hidden="true"></span>
        {{ $evento->prazo_conclusao->format('d/m/Y') }}
        <span class="sr-only">
            ({{ ['verde' => 'no prazo', 'amarelo' => 'prazo próximo', 'vermelho' => 'prazo estourado'][$situacao] }})
        </span>
    </span>
@else
    <span class="text-muted">—</span>
@endif
