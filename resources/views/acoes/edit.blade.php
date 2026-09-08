{{-- Edição de ação corretiva --}}
@extends('layouts.app')

@section('title', 'Editar Ação · SIGEA')
@section('titulo', 'Editar Ação Corretiva — ' . $acao->evento->protocolo)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('acoes.index') }}">Ações</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('conteudo')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <form method="POST" action="{{ route('acoes.update', $acao) }}">
                @csrf
                @method('PUT')
                <div class="card card-outline card-primary">
                    <div class="card-body">
                        @include('acoes.partials.formulario', ['acao' => $acao])
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('eventos.show', ['evento' => $acao->evento_adverso_id, 'aba' => 'acoes']) }}" class="btn btn-outline-secondary">Voltar</a>
                        <div>
                            @can('delete', $acao)
                                <button type="submit" form="form-excluir-acao" class="btn btn-outline-danger"
                                        onclick="return confirm('Remover esta ação corretiva?')">
                                    <i class="far fa-trash-alt mr-1"></i> Remover
                                </button>
                            @endcan
                            <button type="submit" class="btn btn-primary"><i class="far fa-save mr-1"></i> Salvar</button>
                        </div>
                    </div>
                </div>
            </form>

            @can('delete', $acao)
                <form id="form-excluir-acao" method="POST" action="{{ route('acoes.destroy', $acao) }}">
                    @csrf
                    @method('DELETE')
                </form>
            @endcan
        </div>
    </div>
@endsection
