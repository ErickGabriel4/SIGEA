@extends('layouts.app')

@section('title', 'Nova Categoria · SIGEA')
@section('titulo', 'Nova Categoria')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.categorias.index') }}">Categorias</a></li>
    <li class="breadcrumb-item active">Nova</li>
@endsection

@section('conteudo')
    <form method="POST" action="{{ route('admin.categorias.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                @include('admin.categorias.partials.formulario', ['categoria' => null])
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('admin.categorias.index') }}" class="btn btn-outline-secondary">Voltar</a>
                <button class="btn btn-success"><i class="fas fa-plus mr-1"></i> Criar categoria</button>
            </div>
        </div>
    </form>
@endsection
