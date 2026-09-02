@extends('layouts.app')

@section('title', 'Novo Usuário · SIGEA')
@section('titulo', 'Novo Usuário')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.usuarios.index') }}">Usuários</a></li>
    <li class="breadcrumb-item active">Novo</li>
@endsection

@section('conteudo')
    <form method="POST" action="{{ route('admin.usuarios.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                @include('admin.usuarios.partials.formulario', ['usuario' => null])
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">Voltar</a>
                <button class="btn btn-success"><i class="fas fa-user-plus mr-1"></i> Criar usuário</button>
            </div>
        </div>
    </form>
@endsection
