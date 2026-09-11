@extends('relatorios.pdf.base')

@section('titulo-pdf', 'Relatório Consolidado de Eventos Adversos')

@section('corpo')
    <h2>Indicadores de segurança do paciente</h2>
    <table class="grade">
        <tr><th>Indicador</th><th class="direita">Valor</th></tr>
        <tr><td>Total de eventos no período</td><td class="direita">{{ $indicadores['total_eventos'] }}</td></tr>
        <tr><td>Taxa por 1.000 pacientes-dia</td><td class="direita">{{ $indicadores['taxa_por_mil_pacientes_dia'] ?? '—' }}</td></tr>
        <tr><td>Taxa de evitabilidade</td><td class="direita">{{ $indicadores['taxa_evitabilidade'] !== null ? $indicadores['taxa_evitabilidade'] . '%' : '—' }}</td></tr>
        <tr><td>Taxa de eventos com dano</td><td class="direita">{{ $indicadores['taxa_com_dano'] !== null ? $indicadores['taxa_com_dano'] . '%' : '—' }}</td></tr>
        <tr><td>Tempo médio de triagem</td><td class="direita">{{ $indicadores['tempo_medio_triagem_horas'] !== null ? $indicadores['tempo_medio_triagem_horas'] . ' h' : '—' }}</td></tr>
        <tr><td>Tempo médio de conclusão</td><td class="direita">{{ $indicadores['tempo_medio_conclusao_dias'] !== null ? $indicadores['tempo_medio_conclusao_dias'] . ' dias' : '—' }}</td></tr>
        <tr><td>Ações concluídas no prazo</td><td class="direita">{{ $indicadores['taxa_acoes_no_prazo'] !== null ? $indicadores['taxa_acoes_no_prazo'] . '%' : '—' }}</td></tr>
        <tr><td>Aderência ao SLA</td><td class="direita">{{ $indicadores['aderencia_sla'] !== null ? $indicadores['aderencia_sla'] . '%' : '—' }}</td></tr>
    </table>

    <h2>Evolução mensal de eventos</h2>
    @php($maximoSerie = max(1, max($serieTemporal['valores'] ?: [1])))
    <table>
        @foreach ($serieTemporal['rotulos'] as $indice => $rotulo)
            <tr>
                <td style="width:12%">{{ $rotulo }}</td>
                <td style="width:80%"><div class="barra-fundo"><div class="barra" style="width: {{ round(($serieTemporal['valores'][$indice] / $maximoSerie) * 100) }}%"></div></div></td>
                <td class="direita" style="width:8%">{{ $serieTemporal['valores'][$indice] }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Distribuição por grau de dano</h2>
    @php($totalDano = max(1, array_sum($porGrauDano['valores'])))
    <table>
        @foreach ($porGrauDano['rotulos'] as $indice => $rotulo)
            <tr>
                <td style="width:15%">{{ $rotulo }}</td>
                <td style="width:75%"><div class="barra-fundo"><div class="barra" style="width: {{ round(($porGrauDano['valores'][$indice] / $totalDano) * 100) }}%; background:#dc3545"></div></div></td>
                <td class="direita" style="width:10%">{{ $porGrauDano['valores'][$indice] }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Categorias mais frequentes</h2>
    @php($maximoCategoria = max(1, max($porCategoria['valores'] ?: [1])))
    <table>
        @foreach ($porCategoria['rotulos'] as $indice => $rotulo)
            <tr>
                <td style="width:32%">{{ $rotulo }}</td>
                <td style="width:58%"><div class="barra-fundo"><div class="barra" style="width: {{ round(($porCategoria['valores'][$indice] / $maximoCategoria) * 100) }}%"></div></div></td>
                <td class="direita" style="width:10%">{{ $porCategoria['valores'][$indice] }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Eventos por setor</h2>
    @php($maximoSetor = max(1, max($porSetor['valores'] ?: [1])))
    <table>
        @foreach ($porSetor['rotulos'] as $indice => $rotulo)
            <tr>
                <td style="width:15%">{{ $rotulo }}</td>
                <td style="width:75%"><div class="barra-fundo"><div class="barra" style="width: {{ round(($porSetor['valores'][$indice] / $maximoSetor) * 100) }}%; background:#28a745"></div></div></td>
                <td class="direita" style="width:10%">{{ $porSetor['valores'][$indice] }}</td>
            </tr>
        @endforeach
    </table>
@endsection
