<?php

namespace App\Enums;

/**
 * Tipo de ação corretiva.
 */
enum TipoAcao: string
{
    case Corretiva = 'corretiva';
    case Preventiva = 'preventiva';
    case Melhoria = 'melhoria';
    case Treinamento = 'treinamento';
    case Barreira = 'barreira';

    public function label(): string
    {
        return match ($this) {
            self::Corretiva => 'Corretiva',
            self::Preventiva => 'Preventiva',
            self::Melhoria => 'Melhoria',
            self::Treinamento => 'Treinamento',
            self::Barreira => 'Barreira de segurança',
        };
    }
}
