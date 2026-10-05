<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Acesso') · SIGEA</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <span class="h1"><b>SIG</b>EA</span>
            <p class="mb-0 text-muted small">Sistema de Gestão de Eventos Adversos</p>
            <p class="mb-0 text-muted small">IFSP — Câmpus Campinas · Segurança do Paciente</p>
        </div>
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success small">{{ session('status') }}</div>
            @endif

            @yield('conteudo')
        </div>
    </div>
    <p class="text-center text-muted small mt-3 mb-0">Ambiente acadêmico — dados exclusivamente simulados</p>
</div>
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
