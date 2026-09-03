<?php

namespace Database\Seeders;

use App\Models\CategoriaEvento;
use Illuminate\Database\Seeder;

/**
 * Taxonomia de eventos: 13 categorias-raiz da ICPS (WHO, 2009),
 * cada uma com ao menos 3 subcategorias (Seção 5.2).
 */
class CategoriaEventoSeeder extends Seeder
{
    /**
     * raiz => [nome, icps, notificavel_anvisa, exige_investigacao, filhas[]]
     */
    private const TAXONOMIA = [
        'MED' => [
            'nome' => 'Medicação e fluidos intravenosos', 'icps' => 'ICPS-10.1',
            'anvisa' => false, 'investiga' => false,
            'filhas' => [
                ['01', 'Dose incorreta'],
                ['02', 'Medicamento incorreto'],
                ['03', 'Via de administração incorreta'],
                ['04', 'Paciente incorreto'],
                ['05', 'Omissão de dose'],
                ['06', 'Horário incorreto'],
            ],
        ],
        'CIR' => [
            'nome' => 'Procedimento cirúrgico ou invasivo', 'icps' => 'ICPS-10.2',
            'anvisa' => true, 'investiga' => true,
            'filhas' => [
                ['01', 'Cirurgia em local errado'],
                ['02', 'Cirurgia em paciente errado'],
                ['03', 'Retenção de corpo estranho'],
                ['04', 'Complicação intraoperatória evitável'],
            ],
        ],
        'QUE' => [
            'nome' => 'Queda', 'icps' => 'ICPS-10.3',
            'anvisa' => false, 'investiga' => false,
            'filhas' => [
                ['01', 'Queda do leito'],
                ['02', 'Queda da própria altura'],
                ['03', 'Queda durante transporte ou transferência'],
                ['04', 'Queda no banheiro'],
            ],
        ],
        'IRA' => [
            'nome' => 'Infecção relacionada à assistência à saúde', 'icps' => 'ICPS-10.4',
            'anvisa' => true, 'investiga' => true,
            'filhas' => [
                ['01', 'Infecção de corrente sanguínea associada a cateter'],
                ['02', 'Pneumonia associada à ventilação mecânica'],
                ['03', 'Infecção de sítio cirúrgico'],
                ['04', 'Infecção do trato urinário associada a sonda'],
            ],
        ],
        'IDE' => [
            'nome' => 'Identificação do paciente', 'icps' => 'ICPS-10.5',
            'anvisa' => false, 'investiga' => false,
            'filhas' => [
                ['01', 'Paciente sem pulseira de identificação'],
                ['02', 'Identificação incorreta ou trocada'],
                ['03', 'Falha na dupla checagem de identificação'],
            ],
        ],
        'DIS' => [
            'nome' => 'Dispositivos e equipamentos médicos', 'icps' => 'ICPS-10.6',
            'anvisa' => true, 'investiga' => false,
            'filhas' => [
                ['01', 'Falha ou mau funcionamento de equipamento'],
                ['02', 'Equipamento indisponível'],
                ['03', 'Uso incorreto de dispositivo'],
                ['04', 'Perda ou deslocamento de dispositivo invasivo'],
            ],
        ],
        'SAN' => [
            'nome' => 'Sangue e hemoderivados', 'icps' => 'ICPS-10.7',
            'anvisa' => true, 'investiga' => true,
            'filhas' => [
                ['01', 'Reação transfusional'],
                ['02', 'Transfusão de hemocomponente incorreto'],
                ['03', 'Falha na tipagem ou prova cruzada'],
            ],
        ],
        'NUT' => [
            'nome' => 'Dieta e nutrição', 'icps' => 'ICPS-10.8',
            'anvisa' => false, 'investiga' => false,
            'filhas' => [
                ['01', 'Dieta incorreta para o paciente'],
                ['02', 'Broncoaspiração relacionada à dieta'],
                ['03', 'Jejum prolongado indevido'],
            ],
        ],
        'OXI' => [
            'nome' => 'Oxigênio e gases medicinais', 'icps' => 'ICPS-10.9',
            'anvisa' => false, 'investiga' => true,
            'filhas' => [
                ['01', 'Interrupção não programada de oxigenoterapia'],
                ['02', 'Gás incorreto conectado'],
                ['03', 'Falha em válvula ou rede de gases'],
            ],
        ],
        'LPP' => [
            'nome' => 'Lesão por pressão', 'icps' => 'ICPS-10.10',
            'anvisa' => true, 'investiga' => false,
            'filhas' => [
                ['01', 'Lesão por pressão estágio 1 ou 2'],
                ['02', 'Lesão por pressão estágio 3, 4 ou não classificável'],
                ['03', 'Lesão por dispositivo médico'],
            ],
        ],
        'DOC' => [
            'nome' => 'Documentação e prontuário', 'icps' => 'ICPS-10.11',
            'anvisa' => false, 'investiga' => false,
            'filhas' => [
                ['01', 'Registro ausente ou incompleto'],
                ['02', 'Prescrição ilegível ou ambígua'],
                ['03', 'Troca de documentos entre pacientes'],
            ],
        ],
        'LAB' => [
            'nome' => 'Laboratório e diagnóstico', 'icps' => 'ICPS-10.12',
            'anvisa' => false, 'investiga' => false,
            'filhas' => [
                ['01', 'Troca de amostras'],
                ['02', 'Resultado liberado com erro'],
                ['03', 'Atraso crítico na liberação de resultado'],
                ['04', 'Coleta em paciente errado'],
            ],
        ],
        'GES' => [
            'nome' => 'Gestão organizacional e recursos', 'icps' => 'ICPS-10.13',
            'anvisa' => false, 'investiga' => false,
            'filhas' => [
                ['01', 'Dimensionamento insuficiente de equipe'],
                ['02', 'Falta de insumo ou material'],
                ['03', 'Falha de comunicação na transição de cuidado'],
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::TAXONOMIA as $codigo => $dados) {
            $raiz = CategoriaEvento::firstOrCreate(
                ['codigo' => $codigo],
                [
                    'nome' => $dados['nome'],
                    'codigo_icps' => $dados['icps'],
                    'notificavel_anvisa' => $dados['anvisa'],
                    'exige_investigacao' => $dados['investiga'],
                    'ativo' => true,
                ]
            );

            foreach ($dados['filhas'] as [$sufixo, $nome]) {
                CategoriaEvento::firstOrCreate(
                    ['codigo' => "{$codigo}.{$sufixo}"],
                    [
                        'nome' => $nome,
                        'categoria_pai_id' => $raiz->id,
                        'codigo_icps' => "{$dados['icps']}.{$sufixo}",
                        'notificavel_anvisa' => $dados['anvisa'],
                        'exige_investigacao' => $dados['investiga'],
                        'ativo' => true,
                    ]
                );
            }
        }
    }
}
