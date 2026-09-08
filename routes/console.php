<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requer o scheduler ativo: `php artisan schedule:work` (dev) ou cron (produção).
Schedule::command('sigea:marcar-acoes-atrasadas')->dailyAt('06:00');
