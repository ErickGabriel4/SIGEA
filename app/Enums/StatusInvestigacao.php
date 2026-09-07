<?php

namespace App\Enums;

/**
 * Status da investigação de causa raiz.
 */
enum StatusInvestigacao: string
{
    case Aberta = 'aberta';
    case EmAndamento = 'em_andamento';
    case Concluida = 'concluida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Aberta => 'Aberta',
            self::EmAndamento => 'Em andamento',
            self::Concluida => 'Concluída',
            self::Cancelada => 'Cancelada',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::Aberta => 'info',
            self::EmAndamento => 'primary',
            self::Concluida => 'success',
            self::Cancelada => 'dark',
        };
    }
}
