{{-- T02 — Redefinição de senha --}}
@extends('layouts.autenticacao')

@section('titulo', 'Redefinir senha')

@section('conteudo')
    <p class="login-box-msg">Defina sua nova senha (mínimo de 8 caracteres, com letra e número).</p>

    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="input-group mb-3">
            <input type="email" name="email" value="{{ old('email', $request->email) }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="E-mail institucional" required aria-label="E-mail institucional">
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
                   placeholder="Nova senha" required autocomplete="new-password" aria-label="Nova senha">
            <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-lock" aria-hidden="true"></span></div>
            </div>
            @error('password')
                <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="input-group mb-3">
            <input type="password" name="password_confirmation"
                   class="form-control" placeholder="Confirme a nova senha" required
                   autocomplete="new-password" aria-label="Confirmação da nova senha">
            <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-lock" aria-hidden="true"></span></div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Redefinir senha</button>
    </form>
@endsection
