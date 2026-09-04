<?php

namespace App\Enums;

/**
 * Avaliação de evitabilidade do evento adverso.
 */
enum Evitabilidade: string
{
    case Evitavel = 'evitavel';
    case ProvavelmenteEvitavel = 'provavelmente_evitavel';
    case ProvavelmenteInevitavel = 'provavelmente_inevitavel';
    case Inevitavel = 'inevitavel';
    case NaoAvaliado = 'nao_avaliado';

    public function label(): string
    {
        return match ($this) {
            self::Evitavel => 'Evitável',
            self::ProvavelmenteEvitavel => 'Provavelmente evitável',
            self::ProvavelmenteInevitavel => 'Provavelmente inevitável',
            self::Inevitavel => 'Inevitável',
            self::NaoAvaliado => 'Não avaliado',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::Evitavel => 'danger',
            self::ProvavelmenteEvitavel => 'warning',
            self::ProvavelmenteInevitavel => 'info',
            self::Inevitavel => 'secondary',
            self::NaoAvaliado => 'light',
        };
    }

    /** Considerado evitável para o indicador de taxa de evitabilidade. */
    public function contaComoEvitavel(): bool
    {
        return in_array($this, [self::Evitavel, self::ProvavelmenteEvitavel], true);
    }
}
