<?php

namespace App\Enums;

/**
 * Prioridade de tratativa do evento adverso.
 */
enum Prioridade: string
{
    case Baixa = 'baixa';
    case Media = 'media';
    case Alta = 'alta';
    case Critica = 'critica';

    public function label(): string
    {
        return match ($this) {
            self::Baixa => 'Baixa',
            self::Media => 'Média',
            self::Alta => 'Alta',
            self::Critica => 'Crítica',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::Baixa => 'success',
            self::Media => 'info',
            self::Alta => 'warning',
            self::Critica => 'danger',
        };
    }
}
