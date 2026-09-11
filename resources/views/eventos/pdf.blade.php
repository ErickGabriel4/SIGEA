<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Ficha do Evento {{ $evento->protocolo }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #212529; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        h2 { font-size: 13px; border-bottom: 1px solid #999; padding-bottom: 2px; margin: 14px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 3px 5px; vertical-align: top; }
        .rotulo { font-weight: bold; width: 28%; }
        .cabecalho { border-bottom: 2px solid #333; padding-bottom: 6px; margin-bottom: 10px; }
        .grade td, .grade th { border: 1px solid #adb5bd; }
        .rodape { position: fixed; bottom: 0; left: 0; right: 0; font-size: 9px; color: #6c757d; border-top: 1px solid #adb5bd; padding-top: 3px; }
    </style>
</head>
<body>
    <div class="cabecalho">
        <h1>SIGEA — Ficha do Evento Adverso</h1>
        <strong>{{ $evento->protocolo }}</strong> ·
        Status: {{ $evento->status->label() }} ·
        Emitido em {{ now()->format('d/m/Y H:i') }}
    </div>

    <h2>Identificação</h2>
    <table>
        <tr><td class="rotulo">Data/hora da ocorrência</td><td>{{ $evento->data_ocorrencia->format('d/m/Y') }} {{ $evento->hora_ocorrencia ? substr($evento->hora_ocorrencia, 0, 5) : '' }} · Turno: {{ $evento->turno->label() }}</td></tr>
        <tr><td class="rotulo">Setor / local</td><td>{{ $evento->setorOcorrencia->nome }} {{ $evento->local_especifico ? '— ' . $evento->local_especifico : '' }}</td></tr>
        <tr><td class="rotulo">Categoria (ICPS)</td><td>{{ $evento->categoria->nome_completo }} {{ $evento->categoria->codigo_icps ? '(' . $evento->categoria->codigo_icps . ')' : '' }}</td></tr>
        <tr><td class="rotulo">Notificante</td><td>{{ $evento->anonima ? 'Notificação anônima' : ($evento->notificante->nome ?? '—') }}</td></tr>
        <tr><td class="rotulo">Paciente (pseudonimizado)</td><td>{{ $evento->paciente_envolvido ? ($evento->paciente_codigo ?? '—') : 'Sem paciente envolvido' }} · {{ $evento->paciente_faixa_etaria?->label() ?? '—' }} · {{ $evento->paciente_sexo?->label() ?? '—' }}</td></tr>
    </table>

    <h2>Relato</h2>
    <table>
        <tr><td class="rotulo">Descrição</td><td>{{ $evento->descricao }}</td></tr>
        <tr><td class="rotulo">Fatores contribuintes</td><td>{{ $evento->fatores_contribuintes ?? '—' }}</td></tr>
        <tr><td class="rotulo">Ação imediata</td><td>{{ $evento->acao_imediata ?? '—' }}</td></tr>
    </table>

    <h2>Classificação e risco</h2>
    <table>
        <tr><td class="rotulo">Classificação do incidente</td><td>{{ $evento->classificacao->label() }}</td></tr>
        <tr><td class="rotulo">Grau de dano</td><td>{{ $evento->grau_dano->label() }}</td></tr>
        <tr><td class="rotulo">Evitabilidade</td><td>{{ $evento->evitabilidade->label() }}</td></tr>
        <tr><td class="rotulo">Matriz de risco</td><td>Probabilidade {{ $evento->probabilidade ?? '—' }} × Severidade {{ $evento->severidade ?? '—' }} = Score {{ $evento->score_risco ?? '—' }} ({{ $evento->nivel_risco?->label() ?? 'não avaliado' }})</td></tr>
        <tr><td class="rotulo">Prioridade / prazo</td><td>{{ $evento->prioridade->label() }} · Prazo: {{ $evento->prazo_conclusao?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><td class="rotulo">Gestor responsável</td><td>{{ $evento->gestorResponsavel->nome ?? '—' }}</td></tr>
        @if ($evento->notificavel_anvisa)
            <tr><td class="rotulo">NOTIVISA</td><td>{{ $evento->protocolo_notivisa ? 'Protocolo ' . $evento->protocolo_notivisa . ' em ' . $evento->notificado_anvisa_em?->format('d/m/Y') : 'PENDENTE de registro' }}</td></tr>
        @endif
    </table>

    @if ($evento->investigacao)
        <h2>Investigação de causa raiz</h2>
        <table>
            <tr><td class="rotulo">Método / responsável</td><td>{{ $evento->investigacao->metodo->label() }} · {{ $evento->investigacao->responsavel->nome }}</td></tr>
            <tr><td class="rotulo">Status</td><td>{{ $evento->investigacao->status->label() }}</td></tr>
            <tr><td class="rotulo">Causa raiz</td><td>{{ $evento->investigacao->causa_raiz ?? '—' }}</td></tr>
            <tr><td class="rotulo">Conclusão</td><td>{{ $evento->investigacao->conclusao ?? '—' }}</td></tr>
        </table>
        @if ($evento->investigacao->causas->isNotEmpty())
            <table class="grade" style="margin-top:5px">
                <tr><th>Eixo</th><th>Causa</th><th>Raiz?</th></tr>
                @foreach ($evento->investigacao->causas as $causa)
                    <tr>
                        <td>{{ $causa->categoria->label() }}</td>
                        <td>{{ $causa->descricao }}</td>
                        <td>{{ $causa->eh_causa_raiz ? 'Sim' : 'Não' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    @endif

    @if ($evento->acoesCorretivas->isNotEmpty())
        <h2>Ações corretivas</h2>
        <table class="grade">
            <tr><th>Título</th><th>Tipo</th><th>Responsável</th><th>Prazo</th><th>Status</th><th>Eficácia</th></tr>
            @foreach ($evento->acoesCorretivas as $acao)
                <tr>
                    <td>{{ $acao->titulo }}</td>
                    <td>{{ $acao->tipo->label() }}</td>
                    <td>{{ $acao->responsavel->nome }}</td>
                    <td>{{ $acao->prazo->format('d/m/Y') }}</td>
                    <td>{{ $acao->status->label() }}</td>
                    <td>{{ $acao->eficacia->label() }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Histórico do fluxo</h2>
    <table class="grade">
        <tr><th>Data/hora</th><th>De</th><th>Para</th><th>Ação</th><th>Usuário</th></tr>
        @foreach ($evento->historico as $registro)
            <tr>
                <td>{{ $registro->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $registro->status_anterior ?? '—' }}</td>
                <td>{{ $registro->status_novo }}</td>
                <td>{{ str_replace('_', ' ', $registro->acao) }}</td>
                <td>{{ $registro->usuario->nome ?? '—' }}</td>
            </tr>
        @endforeach
    </table>

    <div class="rodape">
        SIGEA — Sistema Integrado de Gestão de Eventos Adversos · Documento gerado com dados exclusivamente simulados ·
        TCC IFSP Câmpus Campinas
    </div>
</body>
</html>
