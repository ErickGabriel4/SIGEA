<?php

namespace App\Services;

use App\Enums\ClassificacaoIncidente;
use App\Models\EventoAdverso;

/**
 * Elegibilidade de notificação compulsória à ANVISA/NOTIVISA.
 * O sistema apenas sinaliza a obrigação — o registro do protocolo e a
 * integração com a API do NOTIVISA ficam para uma etapa futura.
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
}
