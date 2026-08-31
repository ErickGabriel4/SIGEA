{{-- T01 — Tela de login --}}
@extends('layouts.autenticacao')

@section('titulo', 'Entrar')

@section('conteudo')
    <p class="login-box-msg">Acesse com suas credenciais institucionais</p>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="input-group mb-3">
            <input type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="E-mail institucional" required autofocus autocomplete="username"
                   aria-label="E-mail institucional">
            <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-envelope" aria-hidden="true"></span></div>
            </div>
            @error('email')
                <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="input-group mb-3">
            <input type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="Senha" required autocomplete="current-password" aria-label="Senha">
            <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-lock" aria-hidden="true"></span></div>
            </div>
            @error('password')
                <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="row align-items-center">
            <div class="col-7">
                <div class="icheck-primary">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Lembrar-me</label>
                </div>
            </div>
            <div class="col-5">
                <button type="submit" class="btn btn-primary btn-block">Entrar</button>
            </div>
        </div>
    </form>

    <p class="mt-3 mb-0">
        <a href="{{ route('password.request') }}">Esqueci minha senha</a>
    </p>
@endsection
