<?php

namespace App\Enums;

/**
 * Nível de risco derivado do score da matriz 5×5 (Quadro A.13).
 */
enum NivelRisco: string
{
    case Baixo = 'baixo';
    case Moderado = 'moderado';
    case Alto = 'alto';
    case Extremo = 'extremo';

    public function label(): string
    {
        return match ($this) {
            self::Baixo => 'Baixo',
            self::Moderado => 'Moderado',
            self::Alto => 'Alto',
            self::Extremo => 'Extremo',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::Baixo => 'success',
            self::Moderado => 'warning',
            self::Alto => 'orange',
            self::Extremo => 'danger',
        };
    }
}
