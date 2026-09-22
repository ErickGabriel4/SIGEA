<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Agendamentos do SIGEA (Seção 10 do plano de desenvolvimento).
 * Requer o scheduler ativo: `php artisan schedule:work` (dev) ou cron (produção).
 */

// Diário 06:00 — marcar ações vencidas como atrasadas
Schedule::command('sigea:marcar-acoes-atrasadas')->dailyAt('06:00');

// Diário 07:00 — alertar responsáveis por ações vencendo
Schedule::command('sigea:alertar-prazos --somente=acoes')->dailyAt('07:00');

// Diário 07:30 — alertar gestores sobre eventos com prazo próximo
Schedule::command('sigea:alertar-prazos --somente=eventos')->dailyAt('07:30');

// Segunda-feira 08:00 — resumo semanal de indicadores aos gestores
Schedule::command('sigea:resumo-semanal')->weeklyOn(1, '08:00');
