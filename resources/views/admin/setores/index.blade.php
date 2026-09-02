{{-- T18 — Admin: setores --}}
@extends('layouts.app')

@section('title', 'Setores · SIGEA')
@section('titulo', 'Gestão de Setores')
@section('breadcrumb')
    <li class="breadcrumb-item active">Setores</li>
@endsection

@section('conteudo')
    <div class="card">
        <div class="card-header text-right">
            <a href="{{ route('admin.setores.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus mr-1"></i> Novo setor
            </a>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Sigla</th>
                        <th>Tipo</th>
                        <th>Responsável</th>
                        <th class="text-right">Leitos</th>
                        <th class="text-right">Lotados</th>
                        <th>Situação</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($setores as $setor)
                        <tr>
                            <td>{{ $setor->nome }}</td>
                            <td>{{ $setor->sigla }}</td>
                            <td>{{ $setor->tipo->label() }}</td>
                            <td>{{ $setor->responsavel->nome ?? '—' }}</td>
                            <td class="text-right">{{ $setor->leitos ?? '—' }}</td>
                            <td class="text-right">{{ $setor->usuarios_count }}</td>
                            <td><span class="badge badge-{{ $setor->ativo ? 'success' : 'secondary' }}">{{ $setor->ativo ? 'Ativo' : 'Inativo' }}</span></td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('admin.setores.edit', $setor) }}" class="btn btn-outline-primary btn-sm" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.setores.destroy', $setor) }}" class="d-inline"
                                      onsubmit="return confirm('Excluir o setor {{ $setor->nome }}? Setores em uso não podem ser excluídos.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" title="Excluir"><i class="far fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Nenhum setor cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $setores->links() }}
        </div>
    </div>
@endsection
