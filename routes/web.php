<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
 * Mapa de rotas do SIGEA (Seção 7 do plano de desenvolvimento).
 * Negação por padrão: toda rota autenticada exige autenticação explícita.
 */

// Raiz: convidado → login; autenticado → painel
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('painel.index')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    // ── Painel (T02) ───────────────────────────────────────────────────
    Route::get('painel', [DashboardController::class, 'index'])->name('painel.index');
});

require __DIR__ . '/auth.php';
