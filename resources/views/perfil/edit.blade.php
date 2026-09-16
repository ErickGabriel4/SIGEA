{{-- T23 — Perfil do usuário --}}
@extends('layouts.app')

@section('title', 'Meu Perfil · SIGEA')
@section('titulo', 'Meu Perfil')
@section('breadcrumb')
    <li class="breadcrumb-item active">Perfil</li>
@endsection

@section('conteudo')
    <div class="row">
        <div class="col-md-6">
            <form method="POST" action="{{ route('perfil.update') }}">
                @csrf
                @method('PUT')
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Dados pessoais</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="nome">Nome</label>
                            <input type="text" name="nome" id="nome" class="form-control" required maxlength="150"
                                   value="{{ old('nome', $usuario->nome) }}">
                        </div>
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" name="email" id="email" class="form-control" required maxlength="150"
                                   value="{{ old('email', $usuario->email) }}">
                        </div>
                        <div class="form-group">
                            <label for="telefone">Telefone</label>
                            <input type="text" name="telefone" id="telefone" class="form-control" maxlength="20"
                                   value="{{ old('telefone', $usuario->telefone) }}">
                        </div>
                        <dl class="row small text-muted mb-0">
                            <dt class="col-5">Matrícula</dt><dd class="col-7">{{ $usuario->matricula ?? '—' }}</dd>
                            <dt class="col-5">Categoria</dt><dd class="col-7">{{ $usuario->categoria_profissional->label() }}</dd>
                            <dt class="col-5">Setor</dt><dd class="col-7">{{ $usuario->setor->nome ?? '—' }}</dd>
                            <dt class="col-5">Perfil de acesso</dt><dd class="col-7">{{ $usuario->getRoleNames()->join(', ') }}</dd>
                        </dl>
                    </div>
                    <div class="card-footer text-right">
                        <button class="btn btn-primary"><i class="far fa-save mr-1"></i> Salvar</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-md-6">
            <form method="POST" action="{{ route('perfil.senha') }}">
                @csrf
                @method('PUT')
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Alterar senha</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="senha_atual">Senha atual</label>
                            <input type="password" name="senha_atual" id="senha_atual" class="form-control" required autocomplete="current-password">
                        </div>
                        <div class="form-group">
                            <label for="password">Nova senha</label>
                            <input type="password" name="password" id="password" class="form-control" required autocomplete="new-password"
                                   aria-describedby="ajuda-senha">
                            <small id="ajuda-senha" class="form-text text-muted">Mínimo de 8 caracteres, com letra e número.</small>
                        </div>
                        <div class="form-group mb-0">
                            <label for="password_confirmation">Confirmar nova senha</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button class="btn btn-primary"><i class="fas fa-key mr-1"></i> Alterar senha</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
