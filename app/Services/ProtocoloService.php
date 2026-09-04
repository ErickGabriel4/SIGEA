<?php

namespace App\Services;

use App\Models\EventoAdverso;
use Illuminate\Support\Facades\DB;

/**
 * Geração do número de protocolo do evento adverso (Seção 6.3).
 * Formato: EA-{ano}-{sequencial de 6 dígitos}, reiniciado a cada ano.
 */
class ProtocoloService
{
    /**
     * Gera o próximo protocolo do ano, dentro de transação com lock
     * pessimista para evitar colisão em geração simultânea.
     */
    public function gerar(?int $ano = null): string
    {
        $ano = $ano ?? now()->year;

        return DB::transaction(function () use ($ano) {
            $ultimo = EventoAdverso::withTrashed()
                ->where('protocolo', 'like', "EA-{$ano}-%")
                ->lockForUpdate()
                ->orderByDesc('protocolo')
                ->value('protocolo');

            $sequencial = $ultimo !== null
                ? ((int) substr($ultimo, -6)) + 1
                : 1;

            return $this->formatar($ano, $sequencial);
        });
    }

    /** Formata o protocolo no padrão EA-AAAA-NNNNNN. */
    public function formatar(int $ano, int $sequencial): string
    {
        return sprintf('EA-%d-%06d', $ano, $sequencial);
    }
}
