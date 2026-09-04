<?php

namespace App\Enums;

/**
 * Sexo do paciente envolvido.
 */
enum SexoPaciente: string
{
    case Feminino = 'feminino';
    case Masculino = 'masculino';
    case NaoInformado = 'nao_informado';

    public function label(): string
    {
        return match ($this) {
            self::Feminino => 'Feminino',
            self::Masculino => 'Masculino',
            self::NaoInformado => 'Não informado',
        };
    }
}
