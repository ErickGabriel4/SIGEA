{{--
    Wizard de 4 passos da notificação de evento adverso (T04/T08 — UC02).
    Usado por create (novo) e edit (rascunho). Variável $evento é null no create.
--}}
@php($edicao = isset($evento) && $evento !== null)

<form method="POST"
      action="{{ $edicao ? route('eventos.update', $evento) : route('eventos.store') }}"
      enctype="multipart/form-data"
      class="wizard-form"
      id="form-notificacao">
    @csrf
    @if ($edicao) @method('PUT') @endif

    {{-- Stepper (visibilidade do progresso — Nielsen) --}}
    <ol class="stepper" aria-label="Progresso da notificação">
        <li class="passo ativo"><span class="indice">1</span><br>O que aconteceu</li>
        <li class="passo"><span class="indice">2</span><br>Descrição</li>
        <li class="passo"><span class="indice">3</span><br>Envolvidos</li>
        <li class="passo"><span class="indice">4</span><br>Revisão e envio</li>
    </ol>

    {{-- ══ Passo 1 — O que aconteceu ══ --}}
    <div class="wizard-passo" id="wizard-passo-1">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Passo 1 — O que aconteceu</h3></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="categoria_evento_id">
                            Tipo do evento <span class="text-danger">*</span>
                            <i class="fas fa-info-circle text-muted" data-toggle="tooltip"
                               title="Taxonomia baseada na Classificação Internacional de Segurança do Paciente (OMS)." aria-hidden="true"></i>
                        </label>
                        <select name="categoria_evento_id" id="categoria_evento_id" class="form-control" required>
                            <option value="">Selecione a categoria…</option>
                            @foreach ($categoriasRaiz as $raiz)
                                <optgroup label="{{ $raiz->codigo }} — {{ $raiz->nome }}">
                                    @foreach ($raiz->filhas as $filha)
                                        <option value="{{ $filha->id }}"
                                            @selected(old('categoria_evento_id', $evento->categoria_evento_id ?? null) == $filha->id)>
                                            {{ $filha->codigo }} — {{ $filha->nome }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="setor_ocorrencia_id">Setor onde ocorreu <span class="text-danger">*</span></label>
                        <select name="setor_ocorrencia_id" id="setor_ocorrencia_id" class="form-control" required>
                            <option value="">Selecione o setor…</option>
                            @foreach ($setores as $setor)
                                <option value="{{ $setor->id }}"
                                    @selected(old('setor_ocorrencia_id', $evento->setor_ocorrencia_id ?? null) == $setor->id)>
                                    {{ $setor->nome }} ({{ $setor->sigla }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3 col-6">
                        <label for="data_ocorrencia">Data da ocorrência <span class="text-danger">*</span></label>
                        <input type="date" name="data_ocorrencia" id="data_ocorrencia" class="form-control"
                               max="{{ today()->toDateString() }}" required
                               value="{{ old('data_ocorrencia', $evento?->data_ocorrencia?->toDateString() ?? '') }}">
                    </div>
                    <div class="form-group col-md-3 col-6">
                        <label for="hora_ocorrencia">Hora aproximada</label>
                        <input type="time" name="hora_ocorrencia" id="hora_ocorrencia" class="form-control"
                               value="{{ old('hora_ocorrencia', $edicao && $evento->hora_ocorrencia ? substr($evento->hora_ocorrencia, 0, 5) : '') }}">
                    </div>
                    <div class="form-group col-md-3 col-6">
                        <label for="turno">Turno <span class="text-danger">*</span></label>
                        <select name="turno" id="turno" class="form-control" required>
                            @foreach (\App\Enums\Turno::cases() as $turno)
                                <option value="{{ $turno->value }}"
                                    @selected(old('turno', $evento?->turno?->value ?? 'nao_informado') === $turno->value)>
                                    {{ $turno->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3 col-6">
                        <label for="local_especifico">Local específico</label>
                        <input type="text" name="local_especifico" id="local_especifico" class="form-control"
                               placeholder="Ex.: Leito 12, Sala 3" maxlength="150"
                               value="{{ old('local_especifico', $evento->local_especifico ?? '') }}">
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="button" class="btn btn-primary" onclick="SigeaWizard.avancar()">Avançar <i class="fas fa-arrow-right ml-1"></i></button>
            </div>
        </div>
    </div>

    {{-- ══ Passo 2 — Descrição ══ --}}
    <div class="wizard-passo d-none" id="wizard-passo-2">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Passo 2 — Descrição do ocorrido</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="descricao">
                        O que aconteceu? <span class="text-danger">*</span>
                        <i class="fas fa-info-circle text-muted" data-toggle="tooltip"
                           title="Relate os fatos de forma objetiva, sem julgamentos. Mínimo de 30 caracteres. NÃO inclua nome, CPF ou prontuário do paciente." aria-hidden="true"></i>
                    </label>
                    <textarea name="descricao" id="descricao" class="form-control" rows="5" minlength="30" required
                              placeholder="Descreva o ocorrido com detalhes: o quê, quando, como foi percebido…">{{ old('descricao', $evento->descricao ?? '') }}</textarea>
                    <small class="form-text text-muted"><span id="contador-descricao">0</span>/30 caracteres mínimos</small>
                </div>
                <div class="form-group">
                    <label for="fatores_contribuintes">Fatores contribuintes</label>
                    <textarea name="fatores_contribuintes" id="fatores_contribuintes" class="form-control" rows="3"
                              placeholder="Circunstâncias que favoreceram o evento (sobrecarga, ambiente, comunicação…)">{{ old('fatores_contribuintes', $evento->fatores_contribuintes ?? '') }}</textarea>
                </div>
                <div class="form-group">
                    <label for="acao_imediata">Ação imediata adotada</label>
                    <textarea name="acao_imediata" id="acao_imediata" class="form-control" rows="3"
                              placeholder="O que foi feito assim que o evento foi percebido?">{{ old('acao_imediata', $evento->acao_imediata ?? '') }}</textarea>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" onclick="SigeaWizard.voltar()"><i class="fas fa-arrow-left mr-1"></i> Voltar</button>
                <button type="button" class="btn btn-primary" onclick="SigeaWizard.avancar()">Avançar <i class="fas fa-arrow-right ml-1"></i></button>
            </div>
        </div>
    </div>

    {{-- ══ Passo 3 — Envolvidos ══ --}}
    <div class="wizard-passo d-none" id="wizard-passo-3">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Passo 3 — Paciente e profissionais envolvidos</h3></div>
            <div class="card-body">
                <div class="custom-control custom-switch mb-3">
                    <input type="hidden" name="paciente_envolvido" value="0">
                    <input type="checkbox" class="custom-control-input" id="paciente_envolvido" name="paciente_envolvido" value="1"
                           @checked(old('paciente_envolvido', $evento->paciente_envolvido ?? true))>
                    <label class="custom-control-label" for="paciente_envolvido">Houve paciente envolvido</label>
                </div>

                <div id="bloco-paciente">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="paciente_codigo">
                                Identificador interno do paciente
                                <i class="fas fa-shield-alt text-success" data-toggle="tooltip"
                                   title="Privacidade garantida: o identificador digitado é convertido em código pseudonimizado (hash). Nome, CPF ou prontuário nunca são armazenados." aria-hidden="true"></i>
                            </label>
                            <input type="text" name="paciente_codigo" id="paciente_codigo" class="form-control"
                                   maxlength="64" placeholder="Ex.: nº de prontuário"
                                   value="{{ old('paciente_codigo', $evento->paciente_codigo ?? '') }}">
                            <small class="form-text text-muted">Será gravado apenas o código pseudonimizado (LGPD).</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="paciente_faixa_etaria">Faixa etária</label>
                            <select name="paciente_faixa_etaria" id="paciente_faixa_etaria" class="form-control">
                                <option value="">Não informar</option>
                                @foreach (\App\Enums\FaixaEtaria::cases() as $faixa)
                                    <option value="{{ $faixa->value }}"
                                        @selected(old('paciente_faixa_etaria', $evento?->paciente_faixa_etaria?->value ?? '') === $faixa->value)>
                                        {{ $faixa->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="paciente_sexo">Sexo</label>
                            <select name="paciente_sexo" id="paciente_sexo" class="form-control">
                                <option value="">Não informar</option>
                                @foreach (\App\Enums\SexoPaciente::cases() as $sexo)
                                    <option value="{{ $sexo->value }}"
                                        @selected(old('paciente_sexo', $evento?->paciente_sexo?->value ?? '') === $sexo->value)>
                                        {{ $sexo->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <hr>
                <label class="d-block">Profissionais envolvidos ou testemunhas</label>
                <small class="text-muted d-block mb-2">
                    Informe um profissional cadastrado <em>ou</em> um nome externo. A notificação tem caráter não punitivo.
                </small>

                <div id="lista-profissionais">
                    @php($profissionaisAntigos = old('profissionais', $edicao ? $evento->profissionais->map(fn ($p) => ['user_id' => $p->user_id, 'nome_externo' => $p->nome_externo, 'categoria_profissional' => $p->categoria_profissional->value, 'papel' => $p->papel->value])->all() : []))
                    @foreach ($profissionaisAntigos as $indice => $profissional)
                        <div class="form-row linha-profissional">
                            <div class="form-group col-md-4">
                                <select name="profissionais[{{ $indice }}][user_id]" class="form-control form-control-sm">
                                    <option value="">Profissional cadastrado…</option>
                                    @foreach ($usuarios ?? [] as $usuario)
                                        <option value="{{ $usuario->id }}" @selected(($profissional['user_id'] ?? null) == $usuario->id)>{{ $usuario->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <input type="text" name="profissionais[{{ $indice }}][nome_externo]" class="form-control form-control-sm"
                                       placeholder="ou nome externo" value="{{ $profissional['nome_externo'] ?? '' }}">
                            </div>
                            <div class="form-group col-md-2 col-6">
                                <select name="profissionais[{{ $indice }}][categoria_profissional]" class="form-control form-control-sm">
                                    @foreach (\App\Enums\CategoriaProfissional::cases() as $cat)
                                        <option value="{{ $cat->value }}" @selected(($profissional['categoria_profissional'] ?? 'outro') === $cat->value)>{{ $cat->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-2 col-6">
                                <select name="profissionais[{{ $indice }}][papel]" class="form-control form-control-sm">
                                    @foreach (\App\Enums\PapelProfissional::cases() as $papel)
                                        <option value="{{ $papel->value }}" @selected(($profissional['papel'] ?? 'envolvido') === $papel->value)>{{ $papel->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-1">
                                <button type="button" class="btn btn-outline-danger btn-sm btn-block remover-profissional" aria-label="Remover profissional">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" id="adicionar-profissional">
                    <i class="fas fa-plus mr-1"></i> Adicionar profissional
                </button>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" onclick="SigeaWizard.voltar()"><i class="fas fa-arrow-left mr-1"></i> Voltar</button>
                <button type="button" class="btn btn-primary" onclick="SigeaWizard.avancar()">Avançar <i class="fas fa-arrow-right ml-1"></i></button>
            </div>
        </div>
    </div>

    {{-- ══ Passo 4 — Revisão e envio ══ --}}
    <div class="wizard-passo d-none" id="wizard-passo-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Passo 4 — Revisão e envio</h3></div>
            <div class="card-body">
                <div class="callout callout-info" id="resumo-revisao">
                    <p class="text-muted mb-0">Preencha os passos anteriores para ver o resumo.</p>
                </div>

                <div class="form-group">
                    <label for="anexos">Anexos (opcional)</label>
                    <input type="file" name="anexos[]" id="anexos" class="form-control-file" multiple
                           accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx">
                    <small class="form-text text-muted">Até 5 arquivos de 10 MB cada — PDF, JPG, PNG, DOCX ou XLSX.</small>
                </div>

                @if ($edicao && $evento->anexos->isNotEmpty())
                    <p class="mb-1"><strong>Anexos já enviados:</strong></p>
                    <ul class="small">
                        @foreach ($evento->anexos as $anexo)
                            <li>{{ $anexo->nome_original }} ({{ $anexo->tamanho_legivel }})</li>
                        @endforeach
                    </ul>
                @endif

                @if (! $edicao && ($permiteAnonima ?? true))
                    <hr>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="anonima" name="anonima" value="1" @checked(old('anonima'))>
                        <label class="custom-control-label" for="anonima">Desejo notificar <strong>anonimamente</strong></label>
                    </div>
                    <div class="alert alert-warning mt-2 d-none" id="aviso-anonimato" role="alert">
                        <i class="fas fa-user-secret mr-1"></i>
                        <strong>Atenção:</strong> notificações anônimas não recebem retorno. Você pode acompanhá-la
                        anotando o número de protocolo exibido ao final. A opção é <strong>irreversível</strong> e a
                        notificação será enviada imediatamente (sem rascunho).
                    </div>
                @endif
            </div>
            <div class="card-footer d-flex justify-content-between flex-wrap">
                <button type="button" class="btn btn-outline-secondary" onclick="SigeaWizard.voltar()"><i class="fas fa-arrow-left mr-1"></i> Voltar</button>
                <div>
                    <button type="submit" name="acao_formulario" value="rascunho" class="btn btn-outline-primary" id="botao-rascunho">
                        <i class="far fa-save mr-1"></i> Salvar rascunho
                    </button>
                    <button type="submit" name="acao_formulario" value="enviar" class="btn btn-success"
                            onclick="return confirm('Confirma o envio da notificação? Após o envio, o evento seguirá para a triagem do Núcleo de Segurança do Paciente.')">
                        <i class="fas fa-paper-plane mr-1"></i> Enviar notificação
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('[data-toggle="tooltip"]').tooltip();

    // Contador de caracteres da descrição (prevenção de erro)
    const descricao = document.getElementById('descricao');
    const contador = document.getElementById('contador-descricao');
    const atualizarContador = () => { contador.textContent = descricao.value.length; };
    descricao.addEventListener('input', atualizarContador);
    atualizarContador();

    // Alterna bloco do paciente
    const chavePaciente = document.getElementById('paciente_envolvido');
    const blocoPaciente = document.getElementById('bloco-paciente');
    const alternarPaciente = () => blocoPaciente.classList.toggle('d-none', !chavePaciente.checked);
    chavePaciente.addEventListener('change', alternarPaciente);
    alternarPaciente();

    // Aviso de anonimato (controle e liberdade — Nielsen)
    const anonima = document.getElementById('anonima');
    if (anonima) {
        const aviso = document.getElementById('aviso-anonimato');
        const botaoRascunho = document.getElementById('botao-rascunho');
        const alternarAnonimato = () => {
            aviso.classList.toggle('d-none', !anonima.checked);
            botaoRascunho.disabled = anonima.checked;
            botaoRascunho.title = anonima.checked ? 'Notificações anônimas são enviadas imediatamente, sem rascunho.' : '';
        };
        anonima.addEventListener('change', alternarAnonimato);
        alternarAnonimato();
    }

    // Linhas dinâmicas de profissionais
    let indiceProfissional = {{ count($profissionaisAntigos ?? []) }};
    const lista = document.getElementById('lista-profissionais');
    const opcoesUsuarios = `@foreach ($usuarios ?? [] as $usuario)<option value="{{ $usuario->id }}">{{ $usuario->nome }}</option>@endforeach`;
    const opcoesCategorias = `@foreach (\App\Enums\CategoriaProfissional::cases() as $cat)<option value="{{ $cat->value }}">{{ $cat->label() }}</option>@endforeach`;
    const opcoesPapeis = `@foreach (\App\Enums\PapelProfissional::cases() as $papel)<option value="{{ $papel->value }}">{{ $papel->label() }}</option>@endforeach`;

    document.getElementById('adicionar-profissional').addEventListener('click', function () {
        const linha = document.createElement('div');
        linha.className = 'form-row linha-profissional';
        linha.innerHTML = `
            <div class="form-group col-md-4">
                <select name="profissionais[${indiceProfissional}][user_id]" class="form-control form-control-sm">
                    <option value="">Profissional cadastrado…</option>${opcoesUsuarios}
                </select>
            </div>
            <div class="form-group col-md-3">
                <input type="text" name="profissionais[${indiceProfissional}][nome_externo]" class="form-control form-control-sm" placeholder="ou nome externo">
            </div>
            <div class="form-group col-md-2 col-6">
                <select name="profissionais[${indiceProfissional}][categoria_profissional]" class="form-control form-control-sm">${opcoesCategorias}</select>
            </div>
            <div class="form-group col-md-2 col-6">
                <select name="profissionais[${indiceProfissional}][papel]" class="form-control form-control-sm">${opcoesPapeis}</select>
            </div>
            <div class="form-group col-md-1">
                <button type="button" class="btn btn-outline-danger btn-sm btn-block remover-profissional" aria-label="Remover profissional"><i class="fas fa-times"></i></button>
            </div>`;
        lista.appendChild(linha);
        indiceProfissional++;
    });

    lista.addEventListener('click', function (e) {
        const botao = e.target.closest('.remover-profissional');
        if (botao) botao.closest('.linha-profissional').remove();
    });

    // Resumo do passo 4 (reconhecimento em vez de memorização — Nielsen)
    window.montarRevisao = function () {
        const texto = (id) => {
            const el = document.getElementById(id);
            if (!el) return '—';
            if (el.tagName === 'SELECT') return el.selectedOptions[0]?.text?.trim() || '—';
            return el.value || '—';
        };
        const resumo = document.getElementById('resumo-revisao');
        resumo.innerHTML = '';
        const itens = [
            ['Tipo do evento', texto('categoria_evento_id')],
            ['Setor', texto('setor_ocorrencia_id')],
            ['Data/hora', texto('data_ocorrencia') + ' ' + texto('hora_ocorrencia')],
            ['Turno', texto('turno')],
            ['Local', texto('local_especifico')],
            ['Descrição', (document.getElementById('descricao').value || '—').slice(0, 220)],
        ];
        for (const [rotulo, valor] of itens) {
            const p = document.createElement('p');
            p.className = 'mb-1';
            const b = document.createElement('strong');
            b.textContent = rotulo + ': ';
            p.appendChild(b);
            p.appendChild(document.createTextNode(valor));
            resumo.appendChild(p);
        }
    };
});
</script>
@endpush
