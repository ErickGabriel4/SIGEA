<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
 * Mapa de rotas do SIGEA (Seção 7 do plano de desenvolvimento).
 * Negação por padrão: toda rota autenticada exige permissão explícita
 * (spatie/laravel-permission) ou Policy.
 */

// Raiz: convidado → login; autenticado → painel
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('painel.index')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    // ── Painel (T02) — permission:dashboard.ver ────────────────────────
    Route::middleware('permission:dashboard.ver')->prefix('painel')->name('painel.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
    });
});

require __DIR__ . '/auth.php';
