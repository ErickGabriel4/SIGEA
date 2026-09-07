<?php

namespace App\Enums;

/**
 * Eixos do diagrama de Ishikawa (espinha de peixe).
 */
enum CategoriaCausa: string
{
    case Pessoa = 'pessoa';
    case Processo = 'processo';
    case Equipamento = 'equipamento';
    case Ambiente = 'ambiente';
    case Material = 'material';
    case Gestao = 'gestao';

    public function label(): string
    {
        return match ($this) {
            self::Pessoa => 'Pessoa',
            self::Processo => 'Processo',
            self::Equipamento => 'Equipamento',
            self::Ambiente => 'Ambiente',
            self::Material => 'Material',
            self::Gestao => 'Gestão',
        };
    }
}
