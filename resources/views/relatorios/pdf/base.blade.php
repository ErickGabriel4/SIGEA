<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>@yield('titulo-pdf')</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #212529; }
        h1 { font-size: 15px; margin: 0; }
        h2 { font-size: 12px; border-bottom: 1px solid #999; padding-bottom: 2px; margin: 12px 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { padding: 3px 5px; }
        .grade th, .grade td { border: 1px solid #adb5bd; }
        .grade th { background: #e9ecef; }
        .direita { text-align: right; }
        .cabecalho { border-bottom: 2px solid #333; padding-bottom: 6px; margin-bottom: 10px; }
        .barra-fundo { background: #e9ecef; width: 100%; }
        .barra { background: #007bff; height: 9px; }
        .rodape { position: fixed; bottom: 0; left: 0; right: 0; font-size: 9px; color: #6c757d; border-top: 1px solid #adb5bd; padding-top: 3px; }
    </style>
</head>
<body>
    <div class="cabecalho">
        <h1>{{ $instituicao }}</h1>
        <strong>SIGEA — @yield('titulo-pdf')</strong><br>
        Período: {{ \Carbon\Carbon::parse($filtros['inicio'])->format('d/m/Y') }}
        a {{ \Carbon\Carbon::parse($filtros['fim'])->format('d/m/Y') }} ·
        Emitido em {{ $gerado_em->format('d/m/Y H:i') }}
    </div>

    @yield('corpo')

    <div class="rodape">
        SIGEA — Sistema Integrado de Gestão de Eventos Adversos · Dados exclusivamente simulados · TCC IFSP Câmpus Campinas
    </div>
</body>
</html>
