{{-- T20 — Admin: configurações agrupadas por seção --}}
@extends('layouts.app')

@section('title', 'Configurações · SIGEA')
@section('titulo', 'Configurações do Sistema')
@section('breadcrumb')
    <li class="breadcrumb-item active">Configurações</li>
@endsection

@php($rotulosGrupos = [
    'instituicao' => 'Instituição',
    'sla' => 'Prazos (SLA)',
    'notificacao' => 'Notificações',
    'indicadores' => 'Indicadores',
    'geral' => 'Geral',
])

@section('conteudo')
    <form method="POST" action="{{ route('admin.configuracoes.update') }}">
        @csrf
        @method('PUT')

        @foreach ($grupos as $grupo => $configuracoes)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $rotulosGrupos[$grupo] ?? ucfirst($grupo) }}</h3>
                </div>
                <div class="card-body">
                    @foreach ($configuracoes as $config)
                        <div class="form-group row">
                            <label for="cfg-{{ $config->chave }}" class="col-md-4 col-form-label">
                                {{ $config->descricao ?? $config->chave }}
                                <small class="d-block text-muted"><code>{{ $config->chave }}</code></small>
                            </label>
                            <div class="col-md-4">
                                @if ($config->tipo === \App\Enums\TipoConfiguracao::Booleano)
                                    <div class="custom-control custom-switch mt-2">
                                        <input type="hidden" name="configuracoes[{{ $config->chave }}]" value="0">
                                        <input type="checkbox" class="custom-control-input" id="cfg-{{ $config->chave }}"
                                               name="configuracoes[{{ $config->chave }}]" value="1"
                                               @checked(filter_var($config->valor, FILTER_VALIDATE_BOOLEAN))>
                                        <label class="custom-control-label" for="cfg-{{ $config->chave }}">Habilitado</label>
                                    </div>
                                @elseif ($config->tipo === \App\Enums\TipoConfiguracao::Inteiro)
                                    <input type="number" class="form-control" id="cfg-{{ $config->chave }}"
                                           name="configuracoes[{{ $config->chave }}]" value="{{ $config->valor }}" min="0">
                                @else
                                    <input type="text" class="form-control" id="cfg-{{ $config->chave }}"
                                           name="configuracoes[{{ $config->chave }}]" value="{{ $config->valor }}">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="text-right mb-3">
            <button class="btn btn-primary"><i class="far fa-save mr-1"></i> Salvar configurações</button>
        </div>
    </form>
@endsection
