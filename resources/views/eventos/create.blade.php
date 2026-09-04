{{-- T04 — Notificar evento adverso (wizard de 4 passos) --}}
@extends('layouts.app')

@section('title', 'Notificar Evento · SIGEA')
@section('titulo', 'Notificar Evento Adverso')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('eventos.meus') }}">Minhas notificações</a></li>
    <li class="breadcrumb-item active">Notificar</li>
@endsection

@section('conteudo')
    <div class="callout callout-success">
        <i class="fas fa-heart mr-1"></i>
        <strong>Notificar é um ato de cuidado.</strong> Este registro tem caráter educativo e não punitivo,
        conforme o Programa Nacional de Segurança do Paciente. Não inclua dados identificáveis de pacientes.
    </div>

    @include('eventos.partials.formulario', ['evento' => null])
@endsection
