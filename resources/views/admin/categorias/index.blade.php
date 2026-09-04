{{-- T19 — Admin: categorias em árvore (pai/filho) --}}
@extends('layouts.app')

@section('title', 'Categorias · SIGEA')
@section('titulo', 'Taxonomia de Categorias (ICPS)')
@section('breadcrumb')
    <li class="breadcrumb-item active">Categorias</li>
@endsection

@section('conteudo')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="text-muted small">
                Taxonomia em 2 níveis alinhada à <em>International Classification for Patient Safety</em> (WHO, 2009).
            </span>
            <a href="{{ route('admin.categorias.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus mr-1"></i> Nova categoria
            </a>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:12%">Código</th>
                        <th>Nome</th>
                        <th>ICPS</th>
                        <th class="text-center">ANVISA</th>
                        <th class="text-center">Exige investigação</th>
                        <th class="text-right">Eventos</th>
                        <th>Situação</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categorias as $raiz)
                        <tr class="bg-light font-weight-bold">
                            <td>{{ $raiz->codigo }}</td>
                            <td><i class="fas fa-folder-open text-warning mr-1"></i>{{ $raiz->nome }}</td>
                            <td>{{ $raiz->codigo_icps ?? '—' }}</td>
                            <td class="text-center">
                                @if ($raiz->notificavel_anvisa)<span class="badge badge-danger">Sim</span>@else<span class="text-muted">—</span>@endif
                            </td>
                            <td class="text-center">
                                @if ($raiz->exige_investigacao)<span class="badge badge-warning">Sim</span>@else<span class="text-muted">—</span>@endif
                            </td>
                            <td class="text-right">{{ $raiz->eventos_count }}</td>
                            <td><span class="badge badge-{{ $raiz->ativo ? 'success' : 'secondary' }}">{{ $raiz->ativo ? 'Ativa' : 'Inativa' }}</span></td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('admin.categorias.edit', $raiz) }}" class="btn btn-outline-primary btn-sm" title="Editar"><i class="fas fa-pen"></i></a>
                                <form method="POST" action="{{ route('admin.categorias.destroy', $raiz) }}" class="d-inline"
                                      onsubmit="return confirm('Excluir {{ $raiz->codigo }} e suas subcategorias? Categorias em uso não podem ser excluídas.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" title="Excluir"><i class="far fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                        @foreach ($raiz->filhas as $filha)
                            <tr>
                                <td class="pl-4">{{ $filha->codigo }}</td>
                                <td class="pl-4"><i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-2"></i>{{ $filha->nome }}</td>
                                <td>{{ $filha->codigo_icps ?? '—' }}</td>
                                <td class="text-center">
                                    @if ($filha->notificavel_anvisa)<span class="badge badge-danger">Sim</span>@else<span class="text-muted">—</span>@endif
                                </td>
                                <td class="text-center">
                                    @if ($filha->exige_investigacao)<span class="badge badge-warning">Sim</span>@else<span class="text-muted">—</span>@endif
                                </td>
                                <td class="text-right">{{ $filha->eventos_count }}</td>
                                <td><span class="badge badge-{{ $filha->ativo ? 'success' : 'secondary' }}">{{ $filha->ativo ? 'Ativa' : 'Inativa' }}</span></td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('admin.categorias.edit', $filha) }}" class="btn btn-outline-primary btn-sm" title="Editar"><i class="fas fa-pen"></i></a>
                                    <form method="POST" action="{{ route('admin.categorias.destroy', $filha) }}" class="d-inline"
                                          onsubmit="return confirm('Excluir a subcategoria {{ $filha->codigo }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" title="Excluir"><i class="far fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
