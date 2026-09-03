<?php

namespace App\Enums;

/**
 * Tipo de valor do parâmetro de configuração.
 */
enum TipoConfiguracao: string
{
    case Texto = 'string';
    case Inteiro = 'inteiro';
    case Booleano = 'booleano';
    case Json = 'json';

    public function label(): string
    {
        return match ($this) {
            self::Texto => 'Texto',
            self::Inteiro => 'Inteiro',
            self::Booleano => 'Booleano',
            self::Json => 'JSON',
        };
    }
}
