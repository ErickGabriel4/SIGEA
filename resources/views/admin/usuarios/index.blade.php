{{-- T17 — Admin: usuários --}}
@extends('layouts.app')

@section('title', 'Usuários · SIGEA')
@section('titulo', 'Gestão de Usuários')
@section('breadcrumb')
    <li class="breadcrumb-item active">Usuários</li>
@endsection

@section('conteudo')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <form method="GET" action="{{ route('admin.usuarios.index') }}" class="form-inline">
                <input type="text" name="busca" value="{{ request('busca') }}" class="form-control form-control-sm mr-2"
                       placeholder="Nome, e-mail ou matrícula" aria-label="Buscar usuário">
                <select name="perfil" class="form-control form-control-sm mr-2" aria-label="Filtrar por perfil">
                    <option value="">Todos os perfis</option>
                    @foreach ($perfis as $perfil)
                        <option value="{{ $perfil->name }}" @selected(request('perfil') === $perfil->name)>{{ $perfil->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary btn-sm">Buscar</button>
            </form>
            <a href="{{ route('admin.usuarios.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-user-plus mr-1"></i> Novo usuário
            </a>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Setor</th>
                        <th>Situação</th>
                        <th>Último acesso</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr>
                            <td>{{ $usuario->nome }}</td>
                            <td>{{ $usuario->email }}</td>
                            <td>
                                @foreach ($usuario->roles as $perfil)
                                    <span class="badge badge-info">{{ $perfil->name }}</span>
                                @endforeach
                            </td>
                            <td>{{ $usuario->setor->sigla ?? '—' }}</td>
                            <td>
                                <span class="badge badge-{{ $usuario->ativo ? 'success' : 'secondary' }}">
                                    {{ $usuario->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td>{{ $usuario->ultimo_acesso_em?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="btn btn-outline-primary btn-sm" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                @if ($usuario->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.usuarios.alternar-status', $usuario) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-outline-{{ $usuario->ativo ? 'warning' : 'success' }} btn-sm"
                                                title="{{ $usuario->ativo ? 'Suspender acesso' : 'Reativar acesso' }}"
                                                onclick="return confirm('{{ $usuario->ativo ? 'Suspender' : 'Reativar' }} o acesso de {{ $usuario->nome }}?')">
                                            <i class="fas fa-{{ $usuario->ativo ? 'user-slash' : 'user-check' }}"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhum usuário encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $usuarios->links() }}
        </div>
    </div>
@endsection
