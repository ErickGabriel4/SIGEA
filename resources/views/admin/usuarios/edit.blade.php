@extends('layouts.app')

@section('title', 'Editar Usuário · SIGEA')
@section('titulo', 'Editar Usuário — ' . $usuario->nome)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.usuarios.index') }}">Usuários</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('conteudo')
    <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                @include('admin.usuarios.partials.formulario', ['usuario' => $usuario])
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">Voltar</a>
                <button class="btn btn-primary"><i class="far fa-save mr-1"></i> Salvar alterações</button>
            </div>
        </div>
    </form>
@endsection
