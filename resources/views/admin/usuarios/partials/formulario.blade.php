{{-- Campos do formulário de usuário (create/edit) --}}
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="nome">Nome completo <span class="text-danger">*</span></label>
        <input type="text" name="nome" id="nome" class="form-control" maxlength="150" required
               value="{{ old('nome', $usuario->nome ?? '') }}">
    </div>
    <div class="form-group col-md-6">
        <label for="email">E-mail institucional <span class="text-danger">*</span></label>
        <input type="email" name="email" id="email" class="form-control" maxlength="150" required
               value="{{ old('email', $usuario->email ?? '') }}">
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="password">Senha {{ isset($usuario) ? '(deixe em branco para manter)' : '' }} @unless(isset($usuario))<span class="text-danger">*</span>@endunless</label>
        <input type="password" name="password" id="password" class="form-control" autocomplete="new-password"
               @unless(isset($usuario)) required @endunless aria-describedby="ajuda-senha-usuario">
        <small id="ajuda-senha-usuario" class="form-text text-muted">Mínimo de 8 caracteres, com letra e número.</small>
    </div>
    <div class="form-group col-md-6">
        <label for="password_confirmation">Confirmação de senha</label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="new-password"
               @unless(isset($usuario)) required @endunless>
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-3 col-6">
        <label for="matricula">Matrícula</label>
        <input type="text" name="matricula" id="matricula" class="form-control" maxlength="30"
               value="{{ old('matricula', $usuario->matricula ?? '') }}">
    </div>
    <div class="form-group col-md-3 col-6">
        <label for="registro_conselho">Registro de conselho</label>
        <input type="text" name="registro_conselho" id="registro_conselho" class="form-control" maxlength="30"
               placeholder="Ex.: COREN-SP 123456"
               value="{{ old('registro_conselho', $usuario->registro_conselho ?? '') }}">
    </div>
    <div class="form-group col-md-3 col-6">
        <label for="categoria_profissional">Categoria profissional <span class="text-danger">*</span></label>
        <select name="categoria_profissional" id="categoria_profissional" class="form-control" required>
            @foreach (\App\Enums\CategoriaProfissional::cases() as $categoria)
                <option value="{{ $categoria->value }}"
                    @selected(old('categoria_profissional', isset($usuario) ? $usuario->categoria_profissional->value : 'outro') === $categoria->value)>
                    {{ $categoria->label() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-3 col-6">
        <label for="cargo">Cargo</label>
        <input type="text" name="cargo" id="cargo" class="form-control" maxlength="100"
               value="{{ old('cargo', $usuario->cargo ?? '') }}">
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-4">
        <label for="setor_id">Setor de lotação</label>
        <select name="setor_id" id="setor_id" class="form-control">
            <option value="">—</option>
            @foreach ($setores as $setor)
                <option value="{{ $setor->id }}" @selected(old('setor_id', $usuario->setor_id ?? null) == $setor->id)>{{ $setor->nome }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-4">
        <label for="telefone">Telefone</label>
        <input type="text" name="telefone" id="telefone" class="form-control" maxlength="20"
               value="{{ old('telefone', $usuario->telefone ?? '') }}">
    </div>
    <div class="form-group col-md-4">
        <label for="perfil">Perfil de acesso <span class="text-danger">*</span></label>
        <select name="perfil" id="perfil" class="form-control" required>
            @foreach ($perfis as $perfil)
                <option value="{{ $perfil->name }}"
                    @selected(old('perfil', isset($usuario) ? $usuario->getRoleNames()->first() : 'notificador') === $perfil->name)>
                    {{ $perfil->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>
<div class="custom-control custom-switch">
    <input type="hidden" name="ativo" value="0">
    <input type="checkbox" class="custom-control-input" id="ativo" name="ativo" value="1"
           @checked(old('ativo', $usuario->ativo ?? true))>
    <label class="custom-control-label" for="ativo">Acesso ativo</label>
</div>
