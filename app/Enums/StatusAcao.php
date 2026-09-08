<?php

namespace App\Enums;

/**
 * Status da ação corretiva.
 */
enum StatusAcao: string
{
    case Pendente = 'pendente';
    case EmAndamento = 'em_andamento';
    case Concluida = 'concluida';
    case Atrasada = 'atrasada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::EmAndamento => 'Em andamento',
            self::Concluida => 'Concluída',
            self::Atrasada => 'Atrasada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::Pendente => 'secondary',
            self::EmAndamento => 'primary',
            self::Concluida => 'success',
            self::Atrasada => 'danger',
            self::Cancelada => 'dark',
        };
    }

    /** Status considerados "tratados" para permitir a conclusão do evento. */
    public function tratada(): bool
    {
        return in_array($this, [self::Concluida, self::Cancelada], true);
    }
}
