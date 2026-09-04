<?php

namespace App\Enums;

/**
 * Papel do profissional relacionado ao evento.
 */
enum PapelProfissional: string
{
    case Envolvido = 'envolvido';
    case Testemunha = 'testemunha';
    case Notificante = 'notificante';

    public function label(): string
    {
        return match ($this) {
            self::Envolvido => 'Envolvido',
            self::Testemunha => 'Testemunha',
            self::Notificante => 'Notificante',
        };
    }
}
