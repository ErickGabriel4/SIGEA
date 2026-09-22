<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ResumoSemanalIndicadores;
use App\Services\IndicadorService;
use Illuminate\Console\Command;

/**
 * Job semanal (segunda-feira, 08:00): resumo de indicadores aos gestores.
 */
class EnviarResumoSemanal extends Command
{
    protected $signature = 'sigea:resumo-semanal';

    protected $description = 'Envia o resumo semanal de indicadores aos gestores de segurança';

    public function handle(IndicadorService $indicadores): int
    {
        $dados = $indicadores->calcular([
            'inicio' => now()->subDays(7)->toDateString(),
            'fim' => now()->toDateString(),
        ]);

        $gestores = User::ativos()->permission('dashboard.ver')->get();

        foreach ($gestores as $gestor) {
            $gestor->notify(new ResumoSemanalIndicadores($dados));
        }

        $this->info("Resumo semanal enviado a {$gestores->count()} gestor(es).");

        return self::SUCCESS;
    }
}
