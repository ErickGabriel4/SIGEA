@extends('layouts.app')

@section('title', 'Novo Setor · SIGEA')
@section('titulo', 'Novo Setor')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.setores.index') }}">Setores</a></li>
    <li class="breadcrumb-item active">Novo</li>
@endsection

@section('conteudo')
    <form method="POST" action="{{ route('admin.setores.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                @include('admin.setores.partials.formulario', ['setor' => null])
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('admin.setores.index') }}" class="btn btn-outline-secondary">Voltar</a>
                <button class="btn btn-success"><i class="fas fa-plus mr-1"></i> Criar setor</button>
            </div>
        </div>
    </form>
@endsection
