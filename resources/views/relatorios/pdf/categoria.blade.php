@extends('relatorios.pdf.base')

@section('titulo-pdf', 'Relatório de Eventos por Categoria (ICPS)')

@section('corpo')
    <table class="grade">
        <tr>
            <th>Código</th><th>Categoria</th>
            <th class="direita">Eventos</th><th class="direita">Com dano</th><th class="direita">Evitáveis</th>
        </tr>
        @foreach ($linhas as $categoria)
            <tr>
                <td>{{ $categoria->codigo }}</td>
                <td>{{ $categoria->nome }}</td>
                <td class="direita">{{ $categoria->total }}</td>
                <td class="direita">{{ $categoria->com_dano }}</td>
                <td class="direita">{{ $categoria->evitaveis }}</td>
            </tr>
        @endforeach
    </table>
@endsection
