{{-- Campos do formulário de setor --}}
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="nome">Nome <span class="text-danger">*</span></label>
        <input type="text" name="nome" id="nome" class="form-control" maxlength="120" required
               value="{{ old('nome', $setor->nome ?? '') }}">
    </div>
    <div class="form-group col-md-3 col-6">
        <label for="sigla">Sigla <span class="text-danger">*</span></label>
        <input type="text" name="sigla" id="sigla" class="form-control" maxlength="15" required
               value="{{ old('sigla', $setor->sigla ?? '') }}">
    </div>
    <div class="form-group col-md-3 col-6">
        <label for="tipo">Tipo <span class="text-danger">*</span></label>
        <select name="tipo" id="tipo" class="form-control" required>
            @foreach (\App\Enums\TipoSetor::cases() as $tipo)
                <option value="{{ $tipo->value }}"
                    @selected(old('tipo', isset($setor) ? $setor->tipo->value : null) === $tipo->value)>
                    {{ $tipo->label() }}
                </option>
            @endforeach
        </select>
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="responsavel_id">Responsável</label>
        <select name="responsavel_id" id="responsavel_id" class="form-control">
            <option value="">—</option>
            @foreach ($usuarios as $usuario)
                <option value="{{ $usuario->id }}" @selected(old('responsavel_id', $setor->responsavel_id ?? null) == $usuario->id)>{{ $usuario->nome }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-3 col-6">
        <label for="leitos">
            Nº de leitos
            <i class="fas fa-info-circle text-muted" data-toggle="tooltip"
               title="Usado como denominador no indicador de eventos por 1.000 pacientes-dia." aria-hidden="true"></i>
        </label>
        <input type="number" name="leitos" id="leitos" class="form-control" min="0" max="2000"
               value="{{ old('leitos', $setor->leitos ?? '') }}">
    </div>
    <div class="form-group col-md-3 col-6 d-flex align-items-end">
        <div class="custom-control custom-switch mb-2">
            <input type="hidden" name="ativo" value="0">
            <input type="checkbox" class="custom-control-input" id="ativo" name="ativo" value="1"
                   @checked(old('ativo', $setor->ativo ?? true))>
            <label class="custom-control-label" for="ativo">Setor ativo</label>
        </div>
    </div>
</div>
