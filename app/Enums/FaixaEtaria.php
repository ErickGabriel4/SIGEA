<?php

namespace App\Enums;

/**
 * Faixa etária do paciente envolvido.
 */
enum FaixaEtaria: string
{
    case Neonato = 'neonato';
    case Lactente = 'lactente';
    case Infantil = 'infantil';
    case Adolescente = 'adolescente';
    case Adulto = 'adulto';
    case Idoso = 'idoso';

    public function label(): string
    {
        return match ($this) {
            self::Neonato => 'Neonato (0–28 dias)',
            self::Lactente => 'Lactente (29 dias–2 anos)',
            self::Infantil => 'Infantil (2–12 anos)',
            self::Adolescente => 'Adolescente (12–18 anos)',
            self::Adulto => 'Adulto (18–60 anos)',
            self::Idoso => 'Idoso (60+ anos)',
        };
    }
}
