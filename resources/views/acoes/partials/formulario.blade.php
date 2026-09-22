{{-- Campos compartilhados do formulário de ação corretiva (T15) --}}
<div class="form-group">
    <label for="titulo">Título <span class="text-danger">*</span></label>
    <input type="text" name="titulo" id="titulo" class="form-control" maxlength="180" required
           value="{{ old('titulo', $acao->titulo ?? '') }}">
</div>
<div class="form-group">
    <label for="descricao">Descrição <span class="text-danger">*</span></label>
    <textarea name="descricao" id="descricao" class="form-control" rows="3" required>{{ old('descricao', $acao->descricao ?? '') }}</textarea>
</div>
<div class="form-row">
    <div class="form-group col-md-4">
        <label for="tipo">Tipo <span class="text-danger">*</span></label>
        <select name="tipo" id="tipo" class="form-control" required>
            @foreach (\App\Enums\TipoAcao::cases() as $tipo)
                <option value="{{ $tipo->value }}" @selected(old('tipo', isset($acao) ? $acao->tipo->value : null) === $tipo->value)>{{ $tipo->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-4">
        <label for="responsavel_id">Responsável <span class="text-danger">*</span></label>
        <select name="responsavel_id" id="responsavel_id" class="form-control" required>
            <option value="">Selecione…</option>
            @foreach ($responsaveis as $responsavel)
                <option value="{{ $responsavel->id }}" @selected(old('responsavel_id', $acao->responsavel_id ?? null) == $responsavel->id)>
                    {{ $responsavel->nome }}{{ $responsavel->cargo ? ' — ' . $responsavel->cargo : '' }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-4">
        <label for="setor_id">Setor executor</label>
        <select name="setor_id" id="setor_id" class="form-control">
            <option value="">—</option>
            @foreach ($setores as $setor)
                <option value="{{ $setor->id }}" @selected(old('setor_id', $acao->setor_id ?? null) == $setor->id)>{{ $setor->nome }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="form-group">
    <label for="prazo">Prazo <span class="text-danger">*</span></label>
    <input type="date" name="prazo" id="prazo" class="form-control" required
           min="{{ today()->toDateString() }}"
           value="{{ old('prazo', isset($acao) ? $acao->prazo->toDateString() : today()->addDays((int) \App\Models\Configuracao::obter('sla_acao_dias', 60))->toDateString()) }}">
</div>
