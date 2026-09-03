{{-- Campos do formulário de categoria --}}
<div class="form-row">
    <div class="form-group col-md-2 col-6">
        <label for="codigo">Código <span class="text-danger">*</span></label>
        <input type="text" name="codigo" id="codigo" class="form-control" maxlength="15" required
               placeholder="MED ou MED.07" value="{{ old('codigo', $categoria->codigo ?? '') }}">
    </div>
    <div class="form-group col-md-6">
        <label for="nome">Nome <span class="text-danger">*</span></label>
        <input type="text" name="nome" id="nome" class="form-control" maxlength="150" required
               value="{{ old('nome', $categoria->nome ?? '') }}">
    </div>
    <div class="form-group col-md-4 col-6">
        <label for="categoria_pai_id">Categoria-raiz (vazio = é raiz)</label>
        <select name="categoria_pai_id" id="categoria_pai_id" class="form-control">
            <option value="">— nenhuma (categoria-raiz) —</option>
            @foreach ($raizes as $raiz)
                <option value="{{ $raiz->id }}"
                    @selected(old('categoria_pai_id', $categoria->categoria_pai_id ?? null) == $raiz->id)>
                    {{ $raiz->codigo }} — {{ $raiz->nome }}
                </option>
            @endforeach
        </select>
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-8">
        <label for="descricao">Descrição operacional</label>
        <textarea name="descricao" id="descricao" class="form-control" rows="2">{{ old('descricao', $categoria->descricao ?? '') }}</textarea>
    </div>
    <div class="form-group col-md-4">
        <label for="codigo_icps">Código ICPS (WHO, 2009)</label>
        <input type="text" name="codigo_icps" id="codigo_icps" class="form-control" maxlength="20"
               placeholder="ICPS-10.x" value="{{ old('codigo_icps', $categoria->codigo_icps ?? '') }}">
    </div>
</div>
<div class="form-row">
    <div class="col-md-4">
        <div class="custom-control custom-switch">
            <input type="hidden" name="notificavel_anvisa" value="0">
            <input type="checkbox" class="custom-control-input" id="notificavel_anvisa" name="notificavel_anvisa" value="1"
                   @checked(old('notificavel_anvisa', $categoria->notificavel_anvisa ?? false))>
            <label class="custom-control-label" for="notificavel_anvisa">
                Notificável à ANVISA
                <i class="fas fa-info-circle text-muted" data-toggle="tooltip"
                   title="Eventos desta categoria disparam alerta de notificação compulsória ao NOTIVISA." aria-hidden="true"></i>
            </label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="custom-control custom-switch">
            <input type="hidden" name="exige_investigacao" value="0">
            <input type="checkbox" class="custom-control-input" id="exige_investigacao" name="exige_investigacao" value="1"
                   @checked(old('exige_investigacao', $categoria->exige_investigacao ?? false))>
            <label class="custom-control-label" for="exige_investigacao">
                Exige investigação
                <i class="fas fa-info-circle text-muted" data-toggle="tooltip"
                   title="A triagem não poderá dispensar a investigação de causa raiz." aria-hidden="true"></i>
            </label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="custom-control custom-switch">
            <input type="hidden" name="ativo" value="0">
            <input type="checkbox" class="custom-control-input" id="ativo" name="ativo" value="1"
                   @checked(old('ativo', $categoria->ativo ?? true))>
            <label class="custom-control-label" for="ativo">Ativa</label>
        </div>
    </div>
</div>
