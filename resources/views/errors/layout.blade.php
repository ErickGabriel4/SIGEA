<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('codigo') · SIGEA</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
</head>
<body class="hold-transition">
<div class="wrapper d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="text-center px-3">
        <h1 class="display-3 text-@yield('cor', 'warning')">@yield('codigo')</h1>
        <h2 class="h4 mb-3">@yield('mensagem')</h2>
        <p class="text-muted">@yield('detalhe')</p>
        <a href="{{ url('/') }}" class="btn btn-primary">
            <i class="fas fa-home mr-1"></i> Voltar ao início
        </a>
    </div>
</div>
</body>
</html>
