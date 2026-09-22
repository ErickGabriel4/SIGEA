{{-- T15 — Nova ação corretiva para o evento --}}
@extends('layouts.app')

@section('title', 'Nova Ação · SIGEA')
@section('titulo', 'Nova Ação Corretiva — ' . $evento->protocolo)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('eventos.show', ['evento' => $evento, 'aba' => 'acoes']) }}">{{ $evento->protocolo }}</a></li>
    <li class="breadcrumb-item active">Nova ação</li>
@endsection

@section('conteudo')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <form method="POST" action="{{ route('acoes.store', $evento) }}">
                @csrf
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Evento: {{ $evento->categoria->nome_completo }} · {{ $evento->setorOcorrencia->sigla }}</h3>
                    </div>
                    <div class="card-body">
                        @include('acoes.partials.formulario', ['acao' => null])
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('eventos.show', ['evento' => $evento, 'aba' => 'acoes']) }}" class="btn btn-outline-secondary">Voltar</a>
                        <button type="submit" class="btn btn-primary"><i class="far fa-save mr-1"></i> Cadastrar e notificar responsável</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
