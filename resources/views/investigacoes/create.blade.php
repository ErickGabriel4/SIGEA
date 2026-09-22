{{-- Abertura de investigação para o evento (UC09) --}}
@extends('layouts.app')

@section('title', 'Nova Investigação · SIGEA')
@section('titulo', 'Abrir Investigação — ' . $evento->protocolo)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('investigacoes.index') }}">Investigações</a></li>
    <li class="breadcrumb-item active">Nova</li>
@endsection

@section('conteudo')
    <div class="row justify-content-center">
        <div class="col-md-7">
            <form method="POST" action="{{ route('investigacoes.store', $evento) }}">
                @csrf
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Evento: {{ $evento->categoria->nome_completo }} · {{ $evento->setorOcorrencia->sigla }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="responsavel_id">Responsável pela investigação <span class="text-danger">*</span></label>
                            <select name="responsavel_id" id="responsavel_id" class="form-control" required>
                                <option value="">Selecione…</option>
                                @foreach ($responsaveis as $responsavel)
                                    <option value="{{ $responsavel->id }}" @selected(old('responsavel_id', auth()->id()) == $responsavel->id)>
                                        {{ $responsavel->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="metodo">
                                Método de análise <span class="text-danger">*</span>
                                <i class="fas fa-info-circle text-muted" data-toggle="tooltip"
                                   title="5 Porquês: perguntas encadeadas até a causa raiz. Ishikawa: causas organizadas em 6 eixos (pessoa, processo, equipamento, ambiente, material, gestão)." aria-hidden="true"></i>
                            </label>
                            <select name="metodo" id="metodo" class="form-control" required>
                                @foreach (\App\Enums\MetodoInvestigacao::cases() as $metodo)
                                    <option value="{{ $metodo->value }}" @selected(old('metodo') === $metodo->value)>{{ $metodo->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="data_inicio">Data de início <span class="text-danger">*</span></label>
                                <input type="date" name="data_inicio" id="data_inicio" class="form-control" required
                                       max="{{ today()->toDateString() }}"
                                       value="{{ old('data_inicio', today()->toDateString()) }}">
                            </div>
                            <div class="form-group col-6">
                                <label for="data_prevista">Data prevista de conclusão</label>
                                <input type="date" name="data_prevista" id="data_prevista" class="form-control"
                                       value="{{ old('data_prevista', today()->addDays((int) \App\Models\Configuracao::obter('sla_investigacao_dias', 30))->toDateString()) }}">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label for="descricao_analise">Contexto inicial da análise</label>
                            <textarea name="descricao_analise" id="descricao_analise" class="form-control" rows="3"
                                      placeholder="Anotações iniciais, equipe envolvida na análise…">{{ old('descricao_analise') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('eventos.show', $evento) }}" class="btn btn-outline-secondary">Voltar</a>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-search mr-1"></i> Abrir investigação</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () { $('[data-toggle="tooltip"]').tooltip(); });
</script>
@endpush
