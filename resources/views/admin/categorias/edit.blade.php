@extends('layouts.app')

@section('title', 'Editar Categoria · SIGEA')
@section('titulo', 'Editar Categoria — ' . $categoria->codigo)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.categorias.index') }}">Categorias</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('conteudo')
    <form method="POST" action="{{ route('admin.categorias.update', $categoria) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                @include('admin.categorias.partials.formulario', ['categoria' => $categoria])
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('admin.categorias.index') }}" class="btn btn-outline-secondary">Voltar</a>
                <button class="btn btn-primary"><i class="far fa-save mr-1"></i> Salvar alterações</button>
            </div>
        </div>
    </form>
@endsection
