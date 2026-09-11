{{-- Tabela dos 7 indicadores (Seção 6.6) --}}
<table class="table table-bordered table-sm">
    <thead>
        <tr><th>Indicador</th><th class="text-right">Valor</th></tr>
    </thead>
    <tbody>
        <tr><td>Total de eventos no período</td><td class="text-right">{{ $indicadores['total_eventos'] }}</td></tr>
        <tr><td>Taxa de eventos por 1.000 pacientes-dia</td><td class="text-right">{{ $indicadores['taxa_por_mil_pacientes_dia'] ?? '—' }}</td></tr>
        <tr><td>Taxa de evitabilidade</td><td class="text-right">{{ $indicadores['taxa_evitabilidade'] !== null ? $indicadores['taxa_evitabilidade'] . '%' : '—' }}</td></tr>
        <tr><td>Taxa de eventos com dano</td><td class="text-right">{{ $indicadores['taxa_com_dano'] !== null ? $indicadores['taxa_com_dano'] . '%' : '—' }}</td></tr>
        <tr><td>Tempo médio de triagem</td><td class="text-right">{{ $indicadores['tempo_medio_triagem_horas'] !== null ? $indicadores['tempo_medio_triagem_horas'] . ' h' : '—' }}</td></tr>
        <tr><td>Tempo médio de conclusão</td><td class="text-right">{{ $indicadores['tempo_medio_conclusao_dias'] !== null ? $indicadores['tempo_medio_conclusao_dias'] . ' dias' : '—' }}</td></tr>
        <tr><td>Taxa de conclusão de ações no prazo</td><td class="text-right">{{ $indicadores['taxa_acoes_no_prazo'] !== null ? $indicadores['taxa_acoes_no_prazo'] . '%' : '—' }}</td></tr>
        <tr><td>Aderência ao SLA de conclusão</td><td class="text-right">{{ $indicadores['aderencia_sla'] !== null ? $indicadores['aderencia_sla'] . '%' : '—' }}</td></tr>
    </tbody>
</table>
