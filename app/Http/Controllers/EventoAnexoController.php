<?php

namespace App\Http\Controllers;

use App\Models\EventoAdverso;
use App\Models\EventoAnexo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Anexos do evento — upload e download autenticado (storage privado).
 * Atende ao objetivo específico (e) do TCC.
 */
class EventoAnexoController extends Controller
{
    /** Upload de anexo em evento existente. */
    public function store(Request $request, EventoAdverso $evento)
    {
        $this->authorize('anexar', $evento);

        $request->validate(
            ['anexo' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,docx,xlsx']],
            ['anexo.max' => 'O anexo deve ter no máximo 10 MB.',
             'anexo.mimes' => 'Anexos permitidos: PDF, JPG, PNG, DOCX ou XLSX.'],
            ['anexo' => 'anexo']
        );

        $arquivo = $request->file('anexo');
        $caminho = $arquivo->store("privado/eventos/{$evento->id}", 'local');

        $evento->anexos()->create([
            'user_id' => $request->user()->id,
            'nome_original' => $arquivo->getClientOriginalName(),
            'caminho' => $caminho,
            'mime' => $arquivo->getMimeType(),
            'tamanho' => $arquivo->getSize(),
        ]);

        return back()->with('sucesso', 'Anexo adicionado.');
    }

    /** Download por stream autenticado — nunca link público. */
    public function download(Request $request, EventoAdverso $evento, EventoAnexo $anexo)
    {
        $this->authorize('view', $evento);

        abort_unless($anexo->evento_adverso_id === $evento->id, 404);
        abort_unless(Storage::disk('local')->exists($anexo->caminho), 404);

        return Storage::disk('local')->download($anexo->caminho, $anexo->nome_original);
    }

    /** Remove anexo (quem pode anexar pode remover os próprios). */
    public function destroy(Request $request, EventoAdverso $evento, EventoAnexo $anexo)
    {
        $this->authorize('anexar', $evento);

        abort_unless($anexo->evento_adverso_id === $evento->id, 404);

        // Notificador só remove anexos enviados por ele mesmo
        if (! $request->user()->can('evento.ver_todos') && $anexo->user_id !== $request->user()->id) {
            abort(403);
        }

        Storage::disk('local')->delete($anexo->caminho);
        $anexo->delete();

        return back()->with('sucesso', 'Anexo removido.');
    }
}
