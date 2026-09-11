@extends('relatorios.pdf.base')

@section('titulo-pdf', 'Relatório de Ações Corretivas Pendentes')

@section('corpo')
    <table class="grade">
        <tr>
            <th>Ação</th><th>Evento</th><th>Responsável</th><th>Setor</th><th>Prazo</th><th>Status</th>
        </tr>
        @foreach ($acoes as $acao)
            <tr>
                <td>{{ $acao->titulo }}</td>
                <td>{{ $acao->evento->protocolo }}</td>
                <td>{{ $acao->responsavel->nome }}</td>
                <td>{{ $acao->setor->sigla ?? '—' }}</td>
                <td>{{ $acao->prazo->format('d/m/Y') }}</td>
                <td>{{ $acao->status->label() }}</td>
            </tr>
        @endforeach
    </table>
@endsection
