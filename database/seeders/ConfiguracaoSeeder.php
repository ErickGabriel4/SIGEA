<?php

namespace Database\Seeders;

use App\Models\Configuracao;
use Illuminate\Database\Seeder;

/**
 * Parâmetros iniciais do sistema (Seção 5.2 / Quadro A.12).
 */
class ConfiguracaoSeeder extends Seeder
{
    public function run(): void
    {
        $parametros = [
            ['chave' => 'nome_instituicao', 'valor' => 'Hospital Simulado de Ensino IFSP', 'tipo' => 'string', 'grupo' => 'instituicao', 'descricao' => 'Nome exibido em relatórios e cabeçalhos'],
            ['chave' => 'sla_triagem_horas', 'valor' => '48', 'tipo' => 'inteiro', 'grupo' => 'sla', 'descricao' => 'Prazo máximo (horas) para triagem de um evento notificado'],
            ['chave' => 'sla_investigacao_dias', 'valor' => '30', 'tipo' => 'inteiro', 'grupo' => 'sla', 'descricao' => 'Prazo padrão (dias) para conclusão da investigação'],
            ['chave' => 'sla_acao_dias', 'valor' => '60', 'tipo' => 'inteiro', 'grupo' => 'sla', 'descricao' => 'Prazo padrão (dias) para execução de ações corretivas'],
            ['chave' => 'permite_notificacao_anonima', 'valor' => '1', 'tipo' => 'booleano', 'grupo' => 'notificacao', 'descricao' => 'Habilita o registro de notificações anônimas'],
            ['chave' => 'dias_alerta_prazo', 'valor' => '7', 'tipo' => 'inteiro', 'grupo' => 'notificacao', 'descricao' => 'Antecedência (dias) dos alertas de prazo'],
            ['chave' => 'taxa_ocupacao_estimada', 'valor' => '0.8', 'tipo' => 'string', 'grupo' => 'indicadores', 'descricao' => 'Taxa de ocupação estimada usada no cálculo de pacientes-dia'],
        ];

        foreach ($parametros as $parametro) {
            Configuracao::firstOrCreate(['chave' => $parametro['chave']], $parametro);
        }
    }
}
