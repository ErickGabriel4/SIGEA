<?php

namespace App\Console\Commands;

use App\Models\AcaoCorretiva;
use App\Models\Configuracao;
use App\Models\EventoAdverso;
use App\Notifications\AcaoCorretivaVencendo;
use App\Notifications\EventoPrazoProximo;
use Illuminate\Console\Command;

/**
 * Jobs diários (07:00/07:30): alerta responsáveis por ações vencendo e
 * gestores por eventos com prazo de conclusão próximo.
 */
class AlertarPrazos extends Command
{
    protected $signature = 'sigea:alertar-prazos {--somente= : acoes|eventos (vazio = ambos)}';

    protected $description = 'Envia alertas de prazos próximos de ações corretivas e eventos';

    public function handle(): int
    {
        $dias = (int) Configuracao::obter('dias_alerta_prazo', 7);
        $limite = today()->addDays($dias);
        $alvo = $this->option('somente');

        if ($alvo === null || $alvo === 'acoes') {
            $acoes = AcaoCorretiva::query()
                ->whereIn('status', ['pendente', 'em_andamento', 'atrasada'])
                ->whereDate('prazo', '<=', $limite)
                ->with('responsavel')
                ->get();

            foreach ($acoes as $acao) {
                $acao->responsavel?->notify(new AcaoCorretivaVencendo($acao));
            }

            $this->info("Alertas de ações enviados: {$acoes->count()}.");
        }

        if ($alvo === null || $alvo === 'eventos') {
            $eventos = EventoAdverso::query()
                ->whereNotIn('status', ['rascunho', 'concluido', 'cancelado'])
                ->whereNotNull('prazo_conclusao')
                ->whereDate('prazo_conclusao', '<=', $limite)
                ->whereNotNull('gestor_responsavel_id')
                ->with('gestorResponsavel')
                ->get();

            foreach ($eventos as $evento) {
                $evento->gestorResponsavel?->notify(new EventoPrazoProximo($evento));
            }

            $this->info("Alertas de eventos enviados: {$eventos->count()}.");
        }

        return self::SUCCESS;
    }
}
