<?php

namespace App\Enums;

/**
 * Turno de ocorrência do evento.
 */
enum Turno: string
{
    case Manha = 'manha';
    case Tarde = 'tarde';
    case Noite = 'noite';
    case NaoInformado = 'nao_informado';

    public function label(): string
    {
        return match ($this) {
            self::Manha => 'Manhã',
            self::Tarde => 'Tarde',
            self::Noite => 'Noite',
            self::NaoInformado => 'Não informado',
        };
    }
}
