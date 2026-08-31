<?php

namespace App\Enums;

/**
 * Tipo de setor hospitalar.
 */
enum TipoSetor: string
{
    case Clinico = 'clinico';
    case Cirurgico = 'cirurgico';
    case Uti = 'uti';
    case Emergencia = 'emergencia';
    case Ambulatorial = 'ambulatorial';
    case Diagnostico = 'diagnostico';
    case Apoio = 'apoio';
    case Administrativo = 'administrativo';

    public function label(): string
    {
        return match ($this) {
            self::Clinico => 'Clínico',
            self::Cirurgico => 'Cirúrgico',
            self::Uti => 'UTI',
            self::Emergencia => 'Emergência',
            self::Ambulatorial => 'Ambulatorial',
            self::Diagnostico => 'Diagnóstico',
            self::Apoio => 'Apoio',
            self::Administrativo => 'Administrativo',
        };
    }
}
