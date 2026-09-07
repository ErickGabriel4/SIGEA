<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvestigacaoCausaRequest;
use App\Models\Investigacao;
use App\Models\InvestigacaoCausa;

/**
 * Causas da investigação — adição dinâmica nos métodos Ishikawa e 5 Porquês.
 */
class InvestigacaoCausaController extends Controller
{
    public function store(InvestigacaoCausaRequest $request, Investigacao $investigacao)
    {
        $this->authorize('update', $investigacao);

        $dados = $request->validated();
        $dados['ordem'] = $dados['ordem'] ?? ($investigacao->causas()->max('ordem') + 1);

        $investigacao->causas()->create($dados);

        return back()->with('sucesso', 'Causa registrada.');
    }

    public function update(InvestigacaoCausaRequest $request, Investigacao $investigacao, InvestigacaoCausa $causa)
    {
        $this->authorize('update', $investigacao);

        abort_unless($causa->investigacao_id === $investigacao->id, 404);

        $causa->update($request->validated() + ['eh_causa_raiz' => $request->boolean('eh_causa_raiz')]);

        return back()->with('sucesso', 'Causa atualizada.');
    }

    public function destroy(Investigacao $investigacao, InvestigacaoCausa $causa)
    {
        $this->authorize('update', $investigacao);

        abort_unless($causa->investigacao_id === $investigacao->id, 404);

        $causa->delete();

        return back()->with('sucesso', 'Causa removida.');
    }
}
