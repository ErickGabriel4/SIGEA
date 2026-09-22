<?php

namespace App\Console\Commands;

use App\Enums\StatusAcao;
use App\Models\AcaoCorretiva;
use Illuminate\Console\Command;

/**
 * Job diário (06:00): marca como atrasada toda ação pendente/em andamento
 * com prazo vencido (Seção 5.6 / US23).
 */
class MarcarAcoesAtrasadas extends Command
{
    protected $signature = 'sigea:marcar-acoes-atrasadas';

    protected $description = 'Marca como atrasadas as ações corretivas com prazo vencido';

    public function handle(): int
    {
        $total = 0;

        AcaoCorretiva::vencidasAbertas()
            ->get()
            ->each(function (AcaoCorretiva $acao) use (&$total) {
                $acao->update(['status' => StatusAcao::Atrasada]);
                $total++;
            });

        $this->info("Ações marcadas como atrasadas: {$total}.");

        return self::SUCCESS;
    }
}
