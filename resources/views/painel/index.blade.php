{{-- T02 — Painel de indicadores (versão inicial) --}}
@extends('layouts.app')

@section('titulo', 'Painel')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Painel</li>
@endsection

@section('conteudo')
    <div class="callout callout-info">
        <h5 class="mb-1">Bem-vindo(a), {{ auth()->user()->nome }}</h5>
        <p class="mb-0 text-muted">
            Ambiente acadêmico do SIGEA — os indicadores de eventos adversos serão
            incorporados a este painel nas próximas entregas.
        </p>
    </div>

    <div class="row">
        <div class="col-md-6 col-lg-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalSetores }}</h3>
                    <p>Setores ativos</p>
                </div>
                <div class="icon"><i class="fas fa-hospital"></i></div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalLeitos }}</h3>
                    <p>Leitos cadastrados</p>
                </div>
                <div class="icon"><i class="fas fa-bed"></i></div>
            </div>
        </div>
    </div>
@endsection
