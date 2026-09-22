<?php

namespace Database\Seeders;

use App\Enums\StatusEvento;
use App\Models\AcaoCorretiva;
use App\Models\CategoriaEvento;
use App\Models\EventoAdverso;
use App\Models\EventoHistorico;
use App\Models\Investigacao;
use App\Models\Setor;
use App\Models\User;
use App\Services\MatrizRiscoService;
use App\Services\PseudonimizadorService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Massa de 600 eventos adversos simulados nos últimos 24 meses (Seção 10):
 * distribuição realista de dano, sazonalidade leve, concentração em
 * UTI/emergência/clínica médica, ~15% anônimos, investigações em ~40%
 * dos eventos elegíveis e 1–4 ações corretivas por investigação.
 *
 * IMPORTANTE: todos os dados são fictícios — nenhum dado real de paciente.
 */
class EventoSimuladoSeeder extends Seeder
{
    private const TOTAL_EVENTOS = 600;

    /** Peso relativo de cada setor (sigla => peso). */
    private const PESO_SETORES = [
        'UTI-A' => 16, 'PS' => 18, 'CM' => 15, 'UTI-N' => 6, 'CC-ENF' => 9,
        'CC' => 7, 'PED' => 7, 'MAT' => 7, 'AMB' => 5, 'LAB' => 4, 'FAR' => 4, 'IMG' => 2,
    ];

    /** Distribuição do grau de dano (valor => peso %). */
    private const PESO_DANO = [
        'nenhum' => 60, 'leve' => 25, 'moderado' => 10, 'grave' => 4, 'obito' => 1,
    ];

    /** Modelos de descrição por categoria-raiz. */
    private const DESCRICOES = [
        'MED' => 'Durante o preparo e administração de medicamentos, identificou-se divergência entre a prescrição médica e o que foi administrado ao paciente. A equipe percebeu o ocorrido durante a conferência e comunicou imediatamente a enfermeira responsável pelo plantão.',
        'CIR' => 'No procedimento cirúrgico realizado, houve intercorrência relacionada ao protocolo de cirurgia segura. A equipe registrou o ocorrido em prontuário e acionou o cirurgião responsável para as providências imediatas.',
        'QUE' => 'Paciente sofreu queda nas dependências do setor. Foi prontamente atendido pela equipe, avaliado pelo médico plantonista e mantido em observação, com registro do ocorrido em prontuário.',
        'IRA' => 'Identificados sinais sugestivos de infecção relacionada à assistência à saúde em paciente internado. Foram coletadas culturas, iniciada antibioticoterapia conforme protocolo e comunicada a CCIH.',
        'IDE' => 'Detectada falha no processo de identificação do paciente durante a assistência. A conferência de dupla checagem apontou a divergência antes de dano adicional, e o fluxo foi corrigido.',
        'DIS' => 'Equipamento médico-assistencial apresentou falha durante o uso. A engenharia clínica foi acionada, o equipamento foi substituído e identificado com etiqueta de manutenção.',
        'SAN' => 'Durante o processo transfusional, houve intercorrência relacionada ao hemocomponente. A transfusão foi interrompida, o banco de sangue comunicado e o paciente monitorado conforme protocolo.',
        'NUT' => 'Identificada não conformidade na dieta ofertada ao paciente em relação à prescrição nutricional. O serviço de nutrição foi comunicado e a dieta foi substituída.',
        'OXI' => 'Ocorrência envolvendo o suprimento de oxigênio/gases medicinais durante a assistência. A manutenção foi acionada e o paciente permaneceu monitorado, sem intercorrências adicionais.',
        'LPP' => 'Identificada lesão por pressão em paciente internado durante avaliação de rotina da equipe de enfermagem. Instituídas medidas de prevenção e tratamento conforme protocolo institucional.',
        'DOC' => 'Identificada inconsistência na documentação assistencial do paciente. O registro foi corrigido com anotação complementar e o fluxo de documentação reforçado com a equipe.',
        'LAB' => 'Ocorrência no processo de exames laboratoriais/diagnósticos envolvendo amostra ou resultado do paciente. O laboratório foi comunicado e nova coleta/emissão foi providenciada.',
        'GES' => 'Identificada falha organizacional que impactou a assistência ao paciente no período. A coordenação do setor foi comunicada e medidas administrativas imediatas foram adotadas.',
    ];

    public function run(): void
    {
        $matriz = app(MatrizRiscoService::class);
        $pseudonimizador = app(PseudonimizadorService::class);

        $setores = Setor::pluck('id', 'sigla');
        $subcategorias = CategoriaEvento::whereNotNull('categoria_pai_id')
            ->with('pai')->get()->groupBy(fn ($c) => $c->pai->codigo);

        $notificadores = User::role('notificador')->pluck('id');
        $gestores = User::role('gestor_seguranca')->pluck('id');
        $executores = User::whereIn('id', $notificadores)->orWhereIn('id', $gestores)->pluck('id');

        $protocoloSequencia = [];

        for ($i = 0; $i < self::TOTAL_EVENTOS; $i++) {
            $dataOcorrencia = $this->dataComSazonalidade();
            $criadoEm = $dataOcorrencia->copy()->addDays(rand(0, 2))->setTime(rand(7, 22), rand(0, 59));

            $siglaSetor = $this->sortearPonderado(self::PESO_SETORES);
            $raiz = array_rand(self::DESCRICOES);
            $categoria = $subcategorias[$raiz]->random();

            $grauDano = $this->sortearPonderado(self::PESO_DANO);
            $classificacao = $this->classificacaoPorDano($grauDano);
            $anonima = rand(1, 100) <= 15;

            $status = $this->statusPorIdade($criadoEm, $grauDano);

            // Protocolo sequencial por ano (evita 600 transações com lock)
            $ano = $criadoEm->year;
            $protocoloSequencia[$ano] = ($protocoloSequencia[$ano] ?? 0) + 1;
            $protocolo = sprintf('EA-%d-%06d', $ano, $protocoloSequencia[$ano]);

            $evento = new EventoAdverso([
                'anonima' => $anonima,
                'notificante_id' => $anonima ? null : $notificadores->random(),
                'categoria_evento_id' => $categoria->id,
                'setor_ocorrencia_id' => $setores[$siglaSetor],
                'setor_notificante_id' => $setores[$siglaSetor],
                'data_ocorrencia' => $dataOcorrencia->toDateString(),
                'hora_ocorrencia' => sprintf('%02d:%02d', rand(0, 23), rand(0, 59)),
                'turno' => ['manha', 'tarde', 'noite'][rand(0, 2)],
                'local_especifico' => 'Leito ' . rand(1, 40),
                'descricao' => self::DESCRICOES[$raiz],
                'fatores_contribuintes' => rand(0, 1) ? 'Sobrecarga de trabalho e interrupções frequentes durante a atividade.' : null,
                'acao_imediata' => 'Paciente avaliado e monitorado; equipe e chefia imediata comunicadas.',
                'paciente_envolvido' => $raiz !== 'GES',
                'paciente_codigo' => $raiz !== 'GES' ? $pseudonimizador->codificar('PRONT-' . rand(1, 200)) : null,
                'paciente_faixa_etaria' => $raiz !== 'GES' ? ['neonato', 'lactente', 'infantil', 'adolescente', 'adulto', 'adulto', 'idoso', 'idoso'][rand(0, 7)] : null,
                'paciente_sexo' => $raiz !== 'GES' ? ['feminino', 'masculino'][rand(0, 1)] : null,
                'classificacao' => $classificacao,
                'grau_dano' => $grauDano,
                'status' => StatusEvento::Rascunho, // ajustado abaixo
            ]);

            $evento->protocolo = $protocolo;
            $evento->disableLogging(); // massa simulada não polui a auditoria

            $evento->save();

            // Ajuste de linha do tempo conforme o status sorteado
            $this->aplicarLinhaDoTempo($evento, $status, $criadoEm, $gestores, $matriz);

            $evento->timestamps = false;
            $evento->created_at = $criadoEm;
            $evento->updated_at = $criadoEm;
            $evento->saveQuietly();

            // Histórico da criação com data coerente
            EventoHistorico::where('evento_adverso_id', $evento->id)->update(['created_at' => $criadoEm]);

            // Investigações (~40% dos eventos que passaram da triagem com dano) + ações
            if (in_array($status, ['em_investigacao', 'aguardando_acao', 'concluido'], true)
                && $grauDano !== 'nenhum' && rand(1, 100) <= 40) {
                $this->criarInvestigacaoComAcoes($evento, $status, $criadoEm, $gestores, $executores);
            }
        }

        // Rascunhos de demonstração para o notificador fixo
        $demo = User::where('email', 'notificador@sigea.local')->first();
        if ($demo !== null) {
            EventoAdverso::factory(2)->rascunho()->create([
                'notificante_id' => $demo->id,
                'setor_ocorrencia_id' => $setores->first(),
            ]);
        }
    }

    /** Data nos últimos 24 meses com sazonalidade leve (inverno +20%). */
    private function dataComSazonalidade(): Carbon
    {
        do {
            $data = Carbon::now()->subDays(rand(3, 730));
            $peso = in_array($data->month, [5, 6, 7, 8], true) ? 120 : 100;
        } while (rand(1, 120) > $peso);

        return $data->startOfDay();
    }

    /** Sorteio ponderado: [chave => peso] → chave. */
    private function sortearPonderado(array $pesos): string
    {
        $total = array_sum($pesos);
        $sorteio = rand(1, $total);
        $acumulado = 0;

        foreach ($pesos as $chave => $peso) {
            $acumulado += $peso;
            if ($sorteio <= $acumulado) {
                return (string) $chave;
            }
        }

        return (string) array_key_first($pesos);
    }

    /** Classificação coerente com o grau de dano. */
    private function classificacaoPorDano(string $grauDano): string
    {
        if ($grauDano === 'nenhum') {
            return $this->sortearPonderado([
                'near_miss' => 45, 'incidente_sem_dano' => 40, 'circunstancia_notificavel' => 15,
            ]);
        }

        if (in_array($grauDano, ['grave', 'obito'], true) && rand(1, 100) <= 8) {
            return 'never_event';
        }

        return 'evento_adverso';
    }

    /** Status coerente com a idade do evento (antigos majoritariamente concluídos). */
    private function statusPorIdade(Carbon $criadoEm, string $grauDano): string
    {
        $meses = (int) $criadoEm->diffInMonths(now());

        if ($meses >= 6) {
            return $this->sortearPonderado([
                'concluido' => 86, 'aguardando_acao' => 6, 'em_investigacao' => 3, 'cancelado' => 5,
            ]);
        }

        if ($meses >= 2) {
            return $this->sortearPonderado([
                'concluido' => 55, 'aguardando_acao' => 20, 'em_investigacao' => 15,
                'em_triagem' => 5, 'notificado' => 3, 'cancelado' => 2,
            ]);
        }

        return $this->sortearPonderado([
            'concluido' => 20, 'aguardando_acao' => 20, 'em_investigacao' => 20,
            'em_triagem' => 15, 'notificado' => 23, 'cancelado' => 2,
        ]);
    }

    /** Preenche marcos temporais, classificação de risco e histórico do fluxo. */
    private function aplicarLinhaDoTempo(
        EventoAdverso $evento,
        string $status,
        Carbon $criadoEm,
        $gestores,
        MatrizRiscoService $matriz
    ): void {
        $evento->status = StatusEvento::from($status === 'cancelado' ? 'cancelado' : ($status === 'notificado' ? 'notificado' : $status));

        $this->registrarHistorico($evento, 'rascunho', 'notificado', 'envio', $criadoEm);

        if ($status === 'notificado') {
            return;
        }

        if ($status === 'cancelado') {
            $this->registrarHistorico($evento, 'notificado', 'cancelado', 'cancelamento', $criadoEm->copy()->addHours(rand(4, 72)), 'Registro duplicado.');

            return;
        }

        // Triagem
        $triadoEm = $criadoEm->copy()->addHours(rand(2, 96));
        $evento->gestor_responsavel_id = $gestores->random();
        $evento->triado_em = $triadoEm;
        $evento->probabilidade = rand(1, 5);
        $evento->severidade = match ($evento->grau_dano->value) {
            'nenhum' => rand(1, 2),
            'leve' => rand(1, 3),
            'moderado' => rand(2, 4),
            'grave' => rand(4, 5),
            'obito' => 5,
        };
        $evento->evitabilidade = $this->sortearPonderado([
            'evitavel' => 35, 'provavelmente_evitavel' => 30,
            'provavelmente_inevitavel' => 20, 'inevitavel' => 15,
        ]);

        $score = $evento->probabilidade * $evento->severidade;
        $nivel = $matriz->nivelPorScore($score);
        $evento->nivel_risco = $nivel;
        $evento->prioridade = $matriz->prioridadeCorrigida($evento, $nivel);
        $evento->prazo_conclusao = $matriz->prazoConclusao($nivel, $triadoEm);
        $evento->feedback_notificante = 'Obrigado pela notificação. O evento foi analisado pelo NSP e as providências cabíveis foram adotadas.';

        // Elegibilidade ANVISA
        if ($evento->grau_dano->ehGraveOuObito() || $evento->classificacao->value === 'never_event') {
            $evento->notificavel_anvisa = true;
            if (rand(1, 100) <= 70) {
                $evento->protocolo_notivisa = 'NTV-' . rand(100000, 999999);
                $evento->notificado_anvisa_em = $triadoEm->copy()->addDays(rand(1, 5))->toDateString();
            }
        }

        $this->registrarHistorico($evento, 'notificado', 'em_triagem', 'assumir_triagem', $triadoEm);

        if ($status === 'em_triagem') {
            return;
        }

        if (in_array($status, ['em_investigacao', 'aguardando_acao', 'concluido'], true)) {
            $investigadoEm = $triadoEm->copy()->addDays(rand(1, 5));
            $evento->investigado_em = $investigadoEm;
            $this->registrarHistorico($evento, 'em_triagem', 'em_investigacao', 'encaminhamento', $investigadoEm);

            if ($status === 'em_investigacao') {
                return;
            }

            $aguardandoEm = $investigadoEm->copy()->addDays(rand(5, 25));
            $this->registrarHistorico($evento, 'em_investigacao', 'aguardando_acao', 'conclusao_investigacao', $aguardandoEm);

            if ($status === 'aguardando_acao') {
                return;
            }

            $concluidoEm = $aguardandoEm->copy()->addDays(rand(5, 40));
            $evento->concluido_em = $concluidoEm;
            $this->registrarHistorico($evento, 'aguardando_acao', 'concluido', 'conclusao', $concluidoEm);
        }
    }

    /** Grava linha imutável do histórico com data retroativa coerente. */
    private function registrarHistorico(
        EventoAdverso $evento,
        string $de,
        string $para,
        string $acao,
        Carbon $quando,
        ?string $observacao = null
    ): void {
        $historico = new EventoHistorico([
            'evento_adverso_id' => $evento->id,
            'user_id' => $evento->gestor_responsavel_id,
            'status_anterior' => $de,
            'status_novo' => $para,
            'acao' => $acao,
            'observacao' => $observacao,
        ]);
        $historico->created_at = $quando;
        $historico->save();
    }

    /** Cria investigação concluída/andamento com 1–4 ações corretivas. */
    private function criarInvestigacaoComAcoes(
        EventoAdverso $evento,
        string $status,
        Carbon $criadoEm,
        $gestores,
        $executores
    ): void {
        $concluida = in_array($status, ['aguardando_acao', 'concluido'], true);

        $investigacao = new Investigacao([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $gestores->random(),
            'metodo' => ['cinco_porques', 'ishikawa'][rand(0, 1)],
            'data_inicio' => $criadoEm->copy()->addDays(rand(2, 6))->toDateString(),
            'data_prevista' => $criadoEm->copy()->addDays(rand(20, 40))->toDateString(),
            'data_conclusao' => $concluida ? $criadoEm->copy()->addDays(rand(10, 35))->toDateString() : null,
            'descricao_analise' => 'Análise conduzida com a equipe do setor a partir do relato e dos registros do prontuário.',
            'causa_raiz' => $concluida ? 'Ausência de barreira de verificação no processo assistencial.' : null,
            'conclusao' => $concluida ? 'Recomenda-se padronização do processo e treinamento da equipe.' : null,
            'status' => $concluida ? 'concluida' : 'em_andamento',
        ]);
        $investigacao->disableLogging();
        $investigacao->save();

        // Causas (Ishikawa/5 porquês)
        $categorias = ['pessoa', 'processo', 'equipamento', 'ambiente', 'material', 'gestao'];
        $totalCausas = rand(2, 4);
        for ($c = 1; $c <= $totalCausas; $c++) {
            $investigacao->causas()->create([
                'categoria' => $categorias[array_rand($categorias)],
                'descricao' => "Causa contribuinte nº {$c} identificada na análise do evento.",
                'ordem' => $c,
                'eh_causa_raiz' => $c === $totalCausas,
            ]);
        }

        // 1–4 ações corretivas com mix de status
        $totalAcoes = rand(1, 4);
        $tipos = ['corretiva', 'preventiva', 'melhoria', 'treinamento', 'barreira'];
        $titulos = [
            'Revisar o protocolo assistencial do setor',
            'Realizar treinamento da equipe multiprofissional',
            'Implantar dupla checagem no processo',
            'Adequar dimensionamento e escala da equipe',
            'Instalar barreira física/sistêmica de segurança',
        ];

        for ($a = 0; $a < $totalAcoes; $a++) {
            $prazo = $criadoEm->copy()->addDays(rand(20, 80));
            $statusAcao = $evento->status === StatusEvento::Concluido
                ? 'concluida'
                : $this->sortearPonderado(['concluida' => 40, 'em_andamento' => 25, 'pendente' => 20, 'atrasada' => 15]);

            $acao = new AcaoCorretiva([
                'evento_adverso_id' => $evento->id,
                'investigacao_id' => $investigacao->id,
                'titulo' => $titulos[array_rand($titulos)],
                'descricao' => 'Ação definida a partir da causa raiz identificada na investigação do evento.',
                'tipo' => $tipos[array_rand($tipos)],
                'responsavel_id' => $executores->random(),
                'setor_id' => $evento->setor_ocorrencia_id,
                'prazo' => $prazo->toDateString(),
                'data_conclusao' => $statusAcao === 'concluida'
                    ? $prazo->copy()->subDays(rand(-10, 15))->toDateString()
                    : null,
                'status' => $statusAcao,
                'eficacia' => $statusAcao === 'concluida'
                    ? $this->sortearPonderado(['eficaz' => 55, 'parcialmente_eficaz' => 25, 'nao_avaliada' => 15, 'ineficaz' => 5])
                    : 'nao_avaliada',
                'evidencia_conclusao' => $statusAcao === 'concluida'
                    ? 'Evidência registrada: ata de treinamento/registro fotográfico arquivado no NSP.'
                    : null,
            ]);
            $acao->disableLogging();
            $acao->save();
        }
    }
}
