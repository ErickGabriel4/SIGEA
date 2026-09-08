<?php

namespace App\Enums;

/**
 * Avaliação de eficácia da ação corretiva concluída.
 */
enum Eficacia: string
{
    case NaoAvaliada = 'nao_avaliada';
    case Eficaz = 'eficaz';
    case ParcialmenteEficaz = 'parcialmente_eficaz';
    case Ineficaz = 'ineficaz';

    public function label(): string
    {
        return match ($this) {
            self::NaoAvaliada => 'Não avaliada',
            self::Eficaz => 'Eficaz',
            self::ParcialmenteEficaz => 'Parcialmente eficaz',
            self::Ineficaz => 'Ineficaz',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::NaoAvaliada => 'secondary',
            self::Eficaz => 'success',
            self::ParcialmenteEficaz => 'warning',
            self::Ineficaz => 'danger',
        };
    }
}
