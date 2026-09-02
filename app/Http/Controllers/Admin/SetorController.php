<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SetorRequest;
use App\Models\Setor;
use App\Models\User;

/**
 * Cadastro de setores hospitalares (T18 — UC19).
 * Atende ao objetivo específico (e) do TCC.
 */
class SetorController extends Controller
{
    public function index()
    {
        $setores = Setor::query()
            ->with('responsavel')
            ->withCount('usuarios')
            ->orderBy('nome')
            ->paginate(15);

        return view('admin.setores.index', ['setores' => $setores]);
    }

    public function create()
    {
        return view('admin.setores.create', [
            'usuarios' => User::ativos()->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function store(SetorRequest $request)
    {
        $setor = Setor::create($request->validated());

        return redirect()
            ->route('admin.setores.index')
            ->with('sucesso', "Setor {$setor->nome} criado.");
    }

    public function edit(Setor $setore)
    {
        return view('admin.setores.edit', [
            'setor' => $setore,
            'usuarios' => User::ativos()->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function update(SetorRequest $request, Setor $setore)
    {
        $setore->update($request->validated());

        return redirect()
            ->route('admin.setores.index')
            ->with('sucesso', "Setor {$setore->nome} atualizado.");
    }

    /** Exclusão lógica; impedida quando o setor está em uso. */
    public function destroy(Setor $setore)
    {
        if ($setore->usuarios()->exists()) {
            return back()->with('erro', 'Setor em uso não pode ser excluído — inative-o.');
        }

        $setore->delete();

        return redirect()
            ->route('admin.setores.index')
            ->with('sucesso', "Setor {$setore->nome} excluído.");
    }
}
