@extends('relatorios.pdf.base')

@section('titulo-pdf', 'Relatório de Eventos por Setor')

@section('corpo')
    <table class="grade">
        <tr>
            <th>Setor</th><th>Tipo</th>
            <th class="direita">Leitos</th><th class="direita">Eventos</th>
            <th class="direita">Com dano</th><th class="direita">Graves/óbito</th><th class="direita">Concluídos</th>
        </tr>
        @foreach ($linhas as $setor)
            <tr>
                <td>{{ $setor->nome }} ({{ $setor->sigla }})</td>
                <td>{{ $setor->tipo->label() }}</td>
                <td class="direita">{{ $setor->leitos ?? '—' }}</td>
                <td class="direita">{{ $setor->total }}</td>
                <td class="direita">{{ $setor->com_dano }}</td>
                <td class="direita">{{ $setor->graves }}</td>
                <td class="direita">{{ $setor->concluidos }}</td>
            </tr>
        @endforeach
    </table>
@endsection
