<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoriaEventoRequest;
use App\Models\CategoriaEvento;

/**
 * Cadastro da taxonomia de categorias em árvore (T19 — UC19).
 * Atende ao objetivo específico (e) do TCC.
 */
class CategoriaEventoController extends Controller
{
    public function index()
    {
        $categorias = CategoriaEvento::raizes()
            ->with(['filhas' => fn ($q) => $q->withCount('eventos')])
            ->withCount('eventos')
            ->get();

        return view('admin.categorias.index', ['categorias' => $categorias]);
    }

    public function create()
    {
        return view('admin.categorias.create', [
            'raizes' => CategoriaEvento::raizes()->get(),
        ]);
    }

    public function store(CategoriaEventoRequest $request)
    {
        $categoria = CategoriaEvento::create($request->validated());

        return redirect()
            ->route('admin.categorias.index')
            ->with('sucesso', "Categoria {$categoria->codigo} criada.");
    }

    public function edit(CategoriaEvento $categoria)
    {
        return view('admin.categorias.edit', [
            'categoria' => $categoria,
            'raizes' => CategoriaEvento::raizes()->where('id', '!=', $categoria->id)->get(),
        ]);
    }

    public function update(CategoriaEventoRequest $request, CategoriaEvento $categoria)
    {
        // Categoria-raiz com filhas não pode virar subcategoria
        if ($request->filled('categoria_pai_id') && $categoria->filhas()->exists()) {
            return back()->with('erro', 'Categoria-raiz com subcategorias não pode virar subcategoria.');
        }

        $categoria->update($request->validated());

        return redirect()
            ->route('admin.categorias.index')
            ->with('sucesso', "Categoria {$categoria->codigo} atualizada.");
    }

    /** Exclusão lógica; impedida quando em uso, a raiz leva junto as suas subcategorias. */
    public function destroy(CategoriaEvento $categoria)
    {
        $emUso = $categoria->eventos()->exists()
            || $categoria->filhas()->whereHas('eventos')->exists();

        if ($emUso) {
            return back()->with('erro', 'Categoria em uso não pode ser excluída — inative-a.');
        }

        $categoria->filhas()->delete();
        $categoria->delete();

        return redirect()
            ->route('admin.categorias.index')
            ->with('sucesso', "Categoria {$categoria->codigo} excluída.");
    }
}
