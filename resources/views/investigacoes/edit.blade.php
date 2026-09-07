{{-- Edição dos dados da investigação --}}
@extends('layouts.app')

@section('title', 'Editar Investigação · SIGEA')
@section('titulo', 'Editar Investigação — ' . $investigacao->evento->protocolo)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('investigacoes.index') }}">Investigações</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('conteudo')
    <div class="row justify-content-center">
        <div class="col-md-7">
            <form method="POST" action="{{ route('investigacoes.update', $investigacao) }}">
                @csrf
                @method('PUT')
                <div class="card card-outline card-warning">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="responsavel_id">Responsável <span class="text-danger">*</span></label>
                            <select name="responsavel_id" id="responsavel_id" class="form-control" required>
                                @foreach ($responsaveis as $responsavel)
                                    <option value="{{ $responsavel->id }}" @selected(old('responsavel_id', $investigacao->responsavel_id) == $responsavel->id)>
                                        {{ $responsavel->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="metodo">Método <span class="text-danger">*</span></label>
                            <select name="metodo" id="metodo" class="form-control" required>
                                @foreach (\App\Enums\MetodoInvestigacao::cases() as $metodo)
                                    <option value="{{ $metodo->value }}" @selected(old('metodo', $investigacao->metodo->value) === $metodo->value)>
                                        {{ $metodo->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="data_inicio">Início <span class="text-danger">*</span></label>
                                <input type="date" name="data_inicio" id="data_inicio" class="form-control" required
                                       max="{{ today()->toDateString() }}"
                                       value="{{ old('data_inicio', $investigacao->data_inicio->toDateString()) }}">
                            </div>
                            <div class="form-group col-6">
                                <label for="data_prevista">Prevista</label>
                                <input type="date" name="data_prevista" id="data_prevista" class="form-control"
                                       value="{{ old('data_prevista', $investigacao->data_prevista?->toDateString()) }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="descricao_analise">Desenvolvimento da análise</label>
                            <textarea name="descricao_analise" id="descricao_analise" class="form-control" rows="4">{{ old('descricao_analise', $investigacao->descricao_analise) }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="causa_raiz">Causa raiz (síntese)</label>
                            <textarea name="causa_raiz" id="causa_raiz" class="form-control" rows="2">{{ old('causa_raiz', $investigacao->causa_raiz) }}</textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label for="conclusao">Conclusão</label>
                            <textarea name="conclusao" id="conclusao" class="form-control" rows="3">{{ old('conclusao', $investigacao->conclusao) }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('investigacoes.show', $investigacao) }}" class="btn btn-outline-secondary">Voltar</a>
                        <button type="submit" class="btn btn-primary"><i class="far fa-save mr-1"></i> Salvar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
