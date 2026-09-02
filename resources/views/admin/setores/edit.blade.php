@extends('layouts.app')

@section('title', 'Editar Setor · SIGEA')
@section('titulo', 'Editar Setor — ' . $setor->nome)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.setores.index') }}">Setores</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('conteudo')
    <form method="POST" action="{{ route('admin.setores.update', $setor) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                @include('admin.setores.partials.formulario', ['setor' => $setor])
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('admin.setores.index') }}" class="btn btn-outline-secondary">Voltar</a>
                <button class="btn btn-primary"><i class="far fa-save mr-1"></i> Salvar alterações</button>
            </div>
        </div>
    </form>
@endsection
