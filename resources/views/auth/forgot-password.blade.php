{{-- T02 — Recuperação de senha --}}
@extends('layouts.autenticacao')

@section('titulo', 'Recuperar senha')

@section('conteudo')
    <p class="login-box-msg">
        Informe seu e-mail institucional e enviaremos um link para redefinição de senha.
    </p>

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="input-group mb-3">
            <input type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="E-mail institucional" required autofocus aria-label="E-mail institucional">
            <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-envelope" aria-hidden="true"></span></div>
            </div>
            @error('email')
                <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-block">Enviar link de redefinição</button>
    </form>

    <p class="mt-3 mb-0">
        <a href="{{ route('login') }}">Voltar para o login</a>
    </p>
@endsection
