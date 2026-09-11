{{-- T05 — Lista de eventos com filtros (escopo por perfil) --}}
@extends('layouts.app')

@section('title', 'Eventos · SIGEA')
@section('titulo', 'Eventos Adversos')
@section('breadcrumb')
    <li class="breadcrumb-item active">Eventos</li>
@endsection

@section('conteudo')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filtros</h3>
            <div class="card-tools">
                @can('evento.exportar')
                    <a href="{{ route('eventos.exportar', request()->query()) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel mr-1"></i> Exportar XLSX
                    </a>
                    <a href="{{ route('eventos.exportar', array_merge(request()->query(), ['formato' => 'csv'])) }}" class="btn btn-outline-success btn-sm">
                        CSV
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('eventos.index') }}" class="form-row">
                <div class="form-group col-md-2 col-6">
                    <label for="f-protocolo">Protocolo</label>
                    <input type="text" id="f-protocolo" name="protocolo" value="{{ request('protocolo') }}"
                           class="form-control form-control-sm" placeholder="EA-2026-...">
                </div>
                <div class="form-group col-md-2 col-6">
                    <label for="f-inicio">Início</label>
                    <input type="date" id="f-inicio" name="inicio" value="{{ request('inicio') }}" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-2 col-6">
                    <label for="f-fim">Fim</label>
                    <input type="date" id="f-fim" name="fim" value="{{ request('fim') }}" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-2 col-6">
                    <label for="f-status">Status</label>
                    <select id="f-status" name="status" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach (\App\Enums\StatusEvento::cases() as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 col-6">
                    <label for="f-setor">Setor</label>
                    <select id="f-setor" name="setor_id" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach ($setores as $setor)
                            <option value="{{ $setor->id }}" @selected(request('setor_id') == $setor->id)>{{ $setor->sigla }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 col-6">
                    <label for="f-categoria">Categoria</label>
                    <select id="f-categoria" name="categoria_id" class="form-control form-control-sm">
                        <option value="">Todas</option>
                        @foreach ($categoriasRaiz as $categoria)
                            <option value="{{ $categoria->id }}" @selected(request('categoria_id') == $categoria->id)>{{ $categoria->codigo }} — {{ $categoria->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 col-6">
                    <label for="f-dano">Grau de dano</label>
                    <select id="f-dano" name="grau_dano" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach (\App\Enums\GrauDano::cases() as $grau)
                            <option value="{{ $grau->value }}" @selected(request('grau_dano') === $grau->value)>{{ $grau->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 col-6">
                    <label for="f-prioridade">Prioridade</label>
                    <select id="f-prioridade" name="prioridade" class="form-control form-control-sm">
                        <option value="">Todas</option>
                        @foreach (\App\Enums\Prioridade::cases() as $prioridade)
                            <option value="{{ $prioridade->value }}" @selected(request('prioridade') === $prioridade->value)>{{ $prioridade->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 col-6">
                    <label for="f-risco">Nível de risco</label>
                    <select id="f-risco" name="nivel_risco" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach (\App\Enums\NivelRisco::cases() as $nivel)
                            <option value="{{ $nivel->value }}" @selected(request('nivel_risco') === $nivel->value)>{{ $nivel->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 col-6">
                    <label for="f-gestor">Gestor responsável</label>
                    <select id="f-gestor" name="gestor_id" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach ($gestores as $gestor)
                            <option value="{{ $gestor->id }}" @selected(request('gestor_id') == $gestor->id)>{{ $gestor->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-12 mb-0">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Filtrar</button>
                    <a href="{{ route('eventos.index') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>Protocolo</th>
                        <th>Ocorrência</th>
                        <th>Categoria</th>
                        <th>Setor</th>
                        <th>Dano</th>
                        <th>Risco</th>
                        <th>Prioridade</th>
                        <th>Status</th>
                        <th>Prazo</th>
                        <th>Gestor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($eventos as $evento)
                        <tr>
                            <td><a href="{{ route('eventos.show', $evento) }}">{{ $evento->protocolo }}</a></td>
                            <td>{{ $evento->data_ocorrencia->format('d/m/Y') }}</td>
                            <td>{{ $evento->categoria->pai->nome ?? $evento->categoria->nome }}</td>
                            <td>{{ $evento->setorOcorrencia->sigla }}</td>
                            <td><x-badge-dano :dano="$evento->grau_dano" /></td>
                            <td><x-badge-risco :nivel="$evento->nivel_risco" :score="$evento->score_risco" /></td>
                            <td><x-badge-prioridade :prioridade="$evento->prioridade" /></td>
                            <td><x-badge-status :status="$evento->status" /></td>
                            <td><x-indicador-prazo :evento="$evento" /></td>
                            <td>{{ $evento->gestorResponsavel->nome ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">Nenhum evento encontrado com os filtros aplicados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $eventos->links() }}
        </div>
    </div>
@endsection
