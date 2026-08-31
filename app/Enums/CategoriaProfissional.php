<?php

namespace App\Enums;

/**
 * Categoria profissional dos usuários e envolvidos.
 */
enum CategoriaProfissional: string
{
    case Medico = 'medico';
    case Enfermeiro = 'enfermeiro';
    case TecnicoEnfermagem = 'tecnico_enfermagem';
    case Farmaceutico = 'farmaceutico';
    case Fisioterapeuta = 'fisioterapeuta';
    case Nutricionista = 'nutricionista';
    case Psicologo = 'psicologo';
    case Administrativo = 'administrativo';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Medico => 'Médico(a)',
            self::Enfermeiro => 'Enfermeiro(a)',
            self::TecnicoEnfermagem => 'Técnico(a) de enfermagem',
            self::Farmaceutico => 'Farmacêutico(a)',
            self::Fisioterapeuta => 'Fisioterapeuta',
            self::Nutricionista => 'Nutricionista',
            self::Psicologo => 'Psicólogo(a)',
            self::Administrativo => 'Administrativo',
            self::Outro => 'Outro',
        };
    }
}
