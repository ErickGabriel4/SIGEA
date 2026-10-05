{{-- Layout base do SIGEA: estende o AdminLTE 3 (navbar + sidebar por permissão via config/adminlte.php) --}}
@extends('adminlte::page')

@push('css')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endpush

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1 class="h4 mb-0">@yield('titulo')</h1>
        <ol class="breadcrumb bg-transparent mb-0 p-0">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Início</a></li>
            @yield('breadcrumb')
        </ol>
    </div>
@endsection

@section('content')
    {{-- Mensagens flash (visibilidade do estado do sistema — Nielsen) --}}
    @if (session('sucesso'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('sucesso') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    @if (session('erro'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('erro') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Verifique os campos informados:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('conteudo')
@endsection

{{-- Logout exige POST: o item "Sair" da sidebar submete este formulário --}}
@push('js')
    <form id="form-sair" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
    <script>
        document.querySelector('#menu-sair a')?.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('form-sair').submit();
        });
    </script>
@endpush

@section('footer')
    <div class="d-flex justify-content-between small">
        <span>
            <strong>SIGEA</strong> — Sistema de Gestão de Eventos Adversos · v1.0
        </span>
        <span>
            TCC — Tecnologia em Análise e Desenvolvimento de Sistemas · IFSP Câmpus Campinas · Dados exclusivamente simulados
        </span>
    </div>
@endsection
