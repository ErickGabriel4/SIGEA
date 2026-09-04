<?php

namespace App\Enums;

/**
 * Grau de dano ao paciente — escala da ICPS (WHO, 2009).
 */
enum GrauDano: string
{
    case Nenhum = 'nenhum';
    case Leve = 'leve';
    case Moderado = 'moderado';
    case Grave = 'grave';
    case Obito = 'obito';

    public function label(): string
    {
        return match ($this) {
            self::Nenhum => 'Nenhum',
            self::Leve => 'Leve',
            self::Moderado => 'Moderado',
            self::Grave => 'Grave',
            self::Obito => 'Óbito',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::Nenhum => 'success',
            self::Leve => 'info',
            self::Moderado => 'warning',
            self::Grave => 'danger',
            self::Obito => 'dark',
        };
    }

    /** Danos que tornam o evento elegível à notificação compulsória. */
    public function ehGraveOuObito(): bool
    {
        return in_array($this, [self::Grave, self::Obito], true);
    }
}
