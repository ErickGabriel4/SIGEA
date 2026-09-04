{{-- T08 — Editar rascunho (mesmo wizard, pré-preenchido) --}}
@extends('layouts.app')

@section('title', 'Editar Rascunho · SIGEA')
@section('titulo', 'Editar Rascunho de Notificação')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('eventos.meus') }}">Minhas notificações</a></li>
    <li class="breadcrumb-item active">Editar rascunho</li>
@endsection

@section('conteudo')
    <div class="callout callout-warning d-flex justify-content-between align-items-center flex-wrap">
        <span>
            <i class="far fa-save mr-1"></i>
            Este é um <strong>rascunho</strong> — ele ainda não foi enviado ao Núcleo de Segurança do Paciente.
        </span>
        <form method="POST" action="{{ route('eventos.destroy', $evento) }}"
              onsubmit="return confirm('Excluir definitivamente este rascunho?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="far fa-trash-alt mr-1"></i> Excluir rascunho
            </button>
        </form>
    </div>

    @include('eventos.partials.formulario', ['evento' => $evento])
@endsection
