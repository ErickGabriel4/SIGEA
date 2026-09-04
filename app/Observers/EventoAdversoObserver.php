<?php

namespace App\Observers;

use App\Models\EventoAdverso;
use App\Models\EventoHistorico;
use App\Services\ProtocoloService;
use Illuminate\Support\Facades\Auth;

/**
 * Observer do evento adverso: protocolo, anonimato e histórico inicial.
 */
class EventoAdversoObserver
{
    public function __construct(
        private readonly ProtocoloService $protocoloService,
    ) {
    }

    /** Antes de criar: gera o protocolo único e garante o anonimato. */
    public function creating(EventoAdverso $evento): void
    {
        if (blank($evento->protocolo)) {
            $evento->protocolo = $this->protocoloService->gerar();
        }

        // Anonimato irreversível: nenhum vínculo com o notificante desde a criação
        if ($evento->anonima) {
            $evento->notificante_id = null;
        }
    }

    /** Depois de criar: registra a primeira linha do histórico. */
    public function created(EventoAdverso $evento): void
    {
        EventoHistorico::create([
            'evento_adverso_id' => $evento->id,
            'user_id' => $evento->anonima ? null : Auth::id(),
            'status_anterior' => null,
            'status_novo' => $evento->status->value,
            'acao' => 'criacao',
            'observacao' => $evento->anonima ? 'Notificação anônima' : null,
        ]);
    }

    /** Atualização: reforça que evento anônimo jamais recebe notificante. */
    public function updating(EventoAdverso $evento): void
    {
        if ($evento->anonima) {
            $evento->notificante_id = null;
        }
    }
}
