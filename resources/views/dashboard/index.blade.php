{{-- T03 — Painel de indicadores (UC15). Objetivo específico (f) do TCC. --}}
@extends('layouts.app')

@section('title', 'Painel · SIGEA')
@section('titulo', 'Painel de Segurança do Paciente')
@section('breadcrumb')
    <li class="breadcrumb-item active">Painel</li>
@endsection

@section('conteudo')
    {{-- Filtros globais --}}
    <div class="card card-outline card-primary collapsed-card" id="card-filtros">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filtros</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Expandir filtros">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('painel.index') }}" class="form-row">
                <div class="form-group col-md-2 col-6">
                    <label for="filtro-inicio">Início</label>
                    <input type="date" id="filtro-inicio" name="inicio" value="{{ $filtros['inicio'] }}" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-2 col-6">
                    <label for="filtro-fim">Fim</label>
                    <input type="date" id="filtro-fim" name="fim" value="{{ $filtros['fim'] }}" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-3 col-6">
                    <label for="filtro-setor">Setor</label>
                    <select id="filtro-setor" name="setor_id" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach ($setores as $setor)
                            <option value="{{ $setor->id }}" @selected($filtros['setor_id'] == $setor->id)>{{ $setor->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3 col-6">
                    <label for="filtro-categoria">Categoria</label>
                    <select id="filtro-categoria" name="categoria_id" class="form-control form-control-sm">
                        <option value="">Todas</option>
                        @foreach ($categoriasRaiz as $categoria)
                            <option value="{{ $categoria->id }}" @selected($filtros['categoria_id'] == $categoria->id)>{{ $categoria->nome_completo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 col-6">
                    <label for="filtro-dano">Grau de dano</label>
                    <select id="filtro-dano" name="grau_dano" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach (\App\Enums\GrauDano::cases() as $grau)
                            <option value="{{ $grau->value }}" @selected($filtros['grau_dano'] === $grau->value)>{{ $grau->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-12 mb-0">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Aplicar filtros</button>
                    <a href="{{ route('painel.index') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Cards KPI --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <x-card-kpi titulo="Eventos no período" :valor="$indicadores['total_eventos']" icone="fas fa-exclamation-triangle" cor="info" />
        </div>
        <div class="col-lg-3 col-6">
            <x-card-kpi titulo="Eventos com dano" :valor="($indicadores['taxa_com_dano'] ?? 0) . '%'" icone="fas fa-user-injured" cor="warning" />
        </div>
        <div class="col-lg-3 col-6">
            <x-card-kpi titulo="Taxa de evitabilidade" :valor="($indicadores['taxa_evitabilidade'] ?? 0) . '%'" icone="fas fa-shield-alt" cor="success" />
        </div>
        <div class="col-lg-3 col-6">
            <x-card-kpi titulo="Ações em atraso" :valor="$indicadores['acoes_em_atraso']" icone="fas fa-clock" cor="danger" />
        </div>
    </div>

    {{-- Indicadores complementares (Seção 6.6) --}}
    <div class="card">
        <div class="card-body p-2">
            <div class="row text-center">
                <div class="col-md-3 col-6 border-right">
                    <div class="text-muted small" title="Eventos ÷ pacientes-dia estimados × 1.000">
                        Taxa por 1.000 pacientes-dia
                        <i class="fas fa-info-circle" data-toggle="tooltip" aria-hidden="true"></i>
                    </div>
                    <strong>{{ $indicadores['taxa_por_mil_pacientes_dia'] ?? '—' }}</strong>
                </div>
                <div class="col-md-3 col-6 border-right">
                    <div class="text-muted small">Tempo médio de triagem</div>
                    <strong>{{ $indicadores['tempo_medio_triagem_horas'] !== null ? $indicadores['tempo_medio_triagem_horas'] . ' h' : '—' }}</strong>
                </div>
                <div class="col-md-2 col-6 border-right">
                    <div class="text-muted small">Tempo médio de conclusão</div>
                    <strong>{{ $indicadores['tempo_medio_conclusao_dias'] !== null ? $indicadores['tempo_medio_conclusao_dias'] . ' dias' : '—' }}</strong>
                </div>
                <div class="col-md-2 col-6 border-right">
                    <div class="text-muted small">Ações no prazo</div>
                    <strong>{{ $indicadores['taxa_acoes_no_prazo'] !== null ? $indicadores['taxa_acoes_no_prazo'] . '%' : '—' }}</strong>
                </div>
                <div class="col-md-2 col-6">
                    <div class="text-muted small">Aderência ao SLA</div>
                    <strong>{{ $indicadores['aderencia_sla'] !== null ? $indicadores['aderencia_sla'] . '%' : '—' }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Gráficos --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Evolução mensal de eventos</h3></div>
                <div class="card-body"><canvas id="grafico-serie" height="110" role="img" aria-label="Gráfico de linha da evolução mensal de eventos"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Grau de dano</h3></div>
                <div class="card-body"><canvas id="grafico-dano" height="220" role="img" aria-label="Gráfico de rosca da distribuição por grau de dano"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Top 10 categorias</h3></div>
                <div class="card-body"><canvas id="grafico-categoria" height="220" role="img" aria-label="Gráfico de barras horizontais das 10 categorias mais frequentes"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Eventos por setor</h3></div>
                <div class="card-body"><canvas id="grafico-setor" height="220" role="img" aria-label="Gráfico de barras dos eventos por setor"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Matriz de risco 5×5
                        <i class="fas fa-info-circle ml-1" data-toggle="tooltip"
                           title="Probabilidade × Severidade. Clique em uma célula para ver os eventos correspondentes." aria-hidden="true"></i>
                    </h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="matriz-risco" aria-label="Matriz de risco: probabilidade por severidade">
                        <thead>
                            <tr>
                                <th scope="col" rowspan="2" class="align-middle">Probabilidade</th>
                                <th scope="col" colspan="5">Severidade</th>
                            </tr>
                            <tr>
                                @for ($s = 1; $s <= 5; $s++)
                                    <th scope="col">{{ $s }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody id="matriz-corpo">
                            @for ($p = 5; $p >= 1; $p--)
                                <tr>
                                    <th scope="row">{{ $p }}</th>
                                    @for ($s = 1; $s <= 5; $s++)
                                        @php($score = $p * $s)
                                        @php($classe = $score <= 4 ? 'baixo' : ($score <= 9 ? 'moderado' : ($score <= 15 ? 'alto' : 'extremo')))
                                        <td class="celula risco-{{ $classe }}" data-prob="{{ $p }}" data-sev="{{ $s }}"
                                            role="button" tabindex="0"
                                            aria-label="Probabilidade {{ $p }}, severidade {{ $s }}, risco {{ $classe }}">0</td>
                                    @endfor
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    <p class="small text-muted mt-2 mb-0">
                        <span class="badge risco-baixo">Baixo 1–4</span>
                        <span class="badge risco-moderado">Moderado 5–9</span>
                        <span class="badge risco-alto">Alto 10–15</span>
                        <span class="badge risco-extremo">Extremo 16–25</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Eventos mais recentes</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Protocolo</th>
                                <th>Categoria</th>
                                <th>Setor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentes as $evento)
                                <tr>
                                    <td><a href="{{ route('eventos.show', $evento) }}">{{ $evento->protocolo }}</a></td>
                                    <td>{{ $evento->categoria->pai->nome ?? $evento->categoria->nome }}</td>
                                    <td>{{ $evento->setorOcorrencia->sigla }}</td>
                                    <td><x-badge-status :status="$evento->status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Nenhum evento registrado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('[data-toggle="tooltip"]').tooltip();

    const filtros = @json($filtros);
    const consulta = new URLSearchParams(
        Object.fromEntries(Object.entries(filtros).filter(([, v]) => v))
    ).toString();
    const cores = window.SigeaCores;

    function buscar(rota) {
        return fetch(rota + (consulta ? '?' + consulta : ''), { headers: { 'Accept': 'application/json' } })
            .then((r) => r.json());
    }

    // Linha — evolução mensal
    buscar('{{ route('painel.serie') }}').then((dados) => {
        new Chart(document.getElementById('grafico-serie'), {
            type: 'line',
            data: {
                labels: dados.rotulos,
                datasets: [{
                    label: 'Eventos',
                    data: dados.valores,
                    borderColor: cores.primaria,
                    backgroundColor: 'rgba(0,123,255,.12)',
                    fill: true,
                    tension: .3,
                }],
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        });
    });

    // Rosca — grau de dano
    buscar('{{ route('painel.dano') }}').then((dados) => {
        new Chart(document.getElementById('grafico-dano'), {
            type: 'doughnut',
            data: {
                labels: dados.rotulos,
                datasets: [{
                    data: dados.valores,
                    backgroundColor: [cores.sucesso, cores.primaria, cores.aviso, cores.perigo, cores.escuro],
                }],
            },
            options: { plugins: { legend: { position: 'bottom' } } },
        });
    });

    // Barras horizontais — top 10 categorias
    buscar('{{ route('painel.categoria') }}').then((dados) => {
        new Chart(document.getElementById('grafico-categoria'), {
            type: 'bar',
            data: {
                labels: dados.rotulos,
                datasets: [{ label: 'Eventos', data: dados.valores, backgroundColor: cores.primaria }],
            },
            options: {
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    });

    // Barras — por setor
    buscar('{{ route('painel.setor') }}').then((dados) => {
        new Chart(document.getElementById('grafico-setor'), {
            type: 'bar',
            data: {
                labels: dados.rotulos,
                datasets: [{ label: 'Eventos', data: dados.valores, backgroundColor: cores.serie }],
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        });
    });

    // Heatmap — matriz de risco (células clicáveis navegam para a lista filtrada)
    buscar('{{ route('painel.matriz') }}').then((matriz) => {
        document.querySelectorAll('#matriz-corpo .celula').forEach((celula) => {
            const p = celula.dataset.prob;
            const s = celula.dataset.sev;
            const total = (matriz[p] && matriz[p][s]) ? matriz[p][s] : 0;
            celula.textContent = total;
            celula.style.opacity = total === 0 ? '.45' : '1';

            const navegar = () => {
                const params = new URLSearchParams(consulta);
                params.set('probabilidade', p);
                params.set('severidade', s);
                window.location.href = '{{ route('eventos.index') }}?' + params.toString();
            };
            celula.addEventListener('click', navegar);
            celula.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') navegar(); });
        });
    });
});
</script>
@endpush
