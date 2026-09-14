<?php

namespace App\Services;

use App\Enums\ClassificacaoIncidente;
use App\Models\EventoAdverso;

/**
 * Elegibilidade de notificação compulsória à ANVISA/NOTIVISA (Seção 6.4).
 * O sistema apenas sinaliza a obrigação e registra o protocolo manualmente —
 * a integração com a API do NOTIVISA está fora de escopo.
 */
class NotivisaService
{
    /**
     * Evento é elegível quando a categoria exige notificação, o dano é
     * grave/óbito ou o incidente é um never event.
     */
    public function elegivel(EventoAdverso $evento): bool
    {
        $evento->loadMissing('categoria');

        return ($evento->categoria?->notificavel_anvisa ?? false)
            || $evento->grau_dano->ehGraveOuObito()
            || $evento->classificacao === ClassificacaoIncidente::NeverEvent;
    }

    /** Marca o evento como notificável quando elegível (nunca desmarca manualmente marcados). */
    public function aplicar(EventoAdverso $evento): EventoAdverso
    {
        if ($this->elegivel($evento)) {
            $evento->notificavel_anvisa = true;
        }

        return $evento;
    }

    /** Registra o protocolo NOTIVISA informado manualmente pelo gestor. */
    public function registrarProtocolo(EventoAdverso $evento, string $protocolo, ?string $data = null): void
    {
        $evento->forceFill([
            'protocolo_notivisa' => $protocolo,
            'notificado_anvisa_em' => $data ?? today(),
        ])->save();
    }
}
