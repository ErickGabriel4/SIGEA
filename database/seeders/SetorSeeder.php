<?php

namespace Database\Seeders;

use App\Models\Setor;
use Illuminate\Database\Seeder;

/**
 * 12 setores hospitalares simulados (Seção 10).
 */
class SetorSeeder extends Seeder
{
    public const SETORES = [
        ['nome' => 'Unidade de Terapia Intensiva Adulto', 'sigla' => 'UTI-A', 'tipo' => 'uti', 'leitos' => 20],
        ['nome' => 'Unidade de Terapia Intensiva Neonatal', 'sigla' => 'UTI-N', 'tipo' => 'uti', 'leitos' => 12],
        ['nome' => 'Pronto-Socorro', 'sigla' => 'PS', 'tipo' => 'emergencia', 'leitos' => 30],
        ['nome' => 'Clínica Médica', 'sigla' => 'CM', 'tipo' => 'clinico', 'leitos' => 40],
        ['nome' => 'Clínica Cirúrgica', 'sigla' => 'CC-ENF', 'tipo' => 'cirurgico', 'leitos' => 30],
        ['nome' => 'Centro Cirúrgico', 'sigla' => 'CC', 'tipo' => 'cirurgico', 'leitos' => 8],
        ['nome' => 'Pediatria', 'sigla' => 'PED', 'tipo' => 'clinico', 'leitos' => 20],
        ['nome' => 'Maternidade', 'sigla' => 'MAT', 'tipo' => 'clinico', 'leitos' => 25],
        ['nome' => 'Ambulatório de Especialidades', 'sigla' => 'AMB', 'tipo' => 'ambulatorial', 'leitos' => null],
        ['nome' => 'Laboratório de Análises Clínicas', 'sigla' => 'LAB', 'tipo' => 'diagnostico', 'leitos' => null],
        ['nome' => 'Farmácia Hospitalar', 'sigla' => 'FAR', 'tipo' => 'apoio', 'leitos' => null],
        ['nome' => 'Diagnóstico por Imagem', 'sigla' => 'IMG', 'tipo' => 'diagnostico', 'leitos' => null],
    ];

    public function run(): void
    {
        foreach (self::SETORES as $setor) {
            Setor::firstOrCreate(['sigla' => $setor['sigla']], $setor);
        }
    }
}
