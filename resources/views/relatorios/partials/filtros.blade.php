{{-- Filtros + botões de exportação dos relatórios --}}
<div class="card">
    <div class="card-body">
        <form method="GET" class="form-row align-items-end">
            <div class="form-group col-md-3 col-6">
                <label for="r-inicio">Início</label>
                <input type="date" id="r-inicio" name="inicio" value="{{ $filtros['inicio'] }}" class="form-control form-control-sm">
            </div>
            <div class="form-group col-md-3 col-6">
                <label for="r-fim">Fim</label>
                <input type="date" id="r-fim" name="fim" value="{{ $filtros['fim'] }}" class="form-control form-control-sm">
            </div>
            @isset($setores)
                <div class="form-group col-md-3 col-6">
                    <label for="r-setor">Setor</label>
                    <select id="r-setor" name="setor_id" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach ($setores as $setor)
                            <option value="{{ $setor->id }}" @selected(($filtros['setor_id'] ?? null) == $setor->id)>{{ $setor->nome }}</option>
                        @endforeach
                    </select>
                </div>
            @endisset
            <div class="form-group col-md-3 col-6">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync mr-1"></i> Atualizar</button>
                <a href="{{ route('relatorios.pdf', array_merge(['tipo' => $tipo], request()->query())) }}" class="btn btn-danger btn-sm">
                    <i class="far fa-file-pdf mr-1"></i> PDF
                </a>
                <a href="{{ route('relatorios.excel', array_merge(['tipo' => $tipo], request()->query())) }}" class="btn btn-success btn-sm">
                    <i class="far fa-file-excel mr-1"></i> Excel
                </a>
            </div>
        </form>
    </div>
</div>
