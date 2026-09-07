<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComentarioRequest;
use App\Models\EventoAdverso;

/**
 * Comentários do evento (polimórfico). Comentários internos são
 * visíveis apenas a gestores e administradores.
 */
class ComentarioController extends Controller
{
    public function store(ComentarioRequest $request, EventoAdverso $evento)
    {
        $this->authorize('comentar', $evento);

        // Apenas gestores podem marcar comentário como interno
        $interno = $request->boolean('interno') && $request->user()->can('evento.ver_todos');

        $evento->comentarios()->create([
            'user_id' => $request->user()->id,
            'corpo' => $request->validated('corpo'),
            'interno' => $interno,
        ]);

        return back()->with('sucesso', 'Comentário adicionado.');
    }
}
