<?php

use App\Http\Controllers\Admin\CategoriaEventoController;
use App\Http\Controllers\Admin\ConfiguracaoController;
use App\Http\Controllers\Admin\SetorController;
use App\Http\Controllers\Admin\UsuarioController;
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

    // ── Administração (T17/T18) — role:administrador ───────────────────
    Route::middleware('role:administrador')->prefix('admin')->name('admin.')->group(function () {
        Route::post('/usuarios/{usuario}/alternar-status', [UsuarioController::class, 'alternarStatus'])
            ->name('usuarios.alternar-status');
        Route::resource('usuarios', UsuarioController::class)
            ->except(['show'])->parameters(['usuarios' => 'usuario']);

        Route::resource('setores', SetorController::class)->except(['show']);

        // ── Taxonomia de categorias (T19) ──────────────────────────────
        Route::resource('categorias', CategoriaEventoController::class)
            ->except(['show'])->parameters(['categorias' => 'categoria']);

        // ── Parâmetros do sistema (T20) ────────────────────────────────
        Route::get('/configuracoes', [ConfiguracaoController::class, 'index'])->name('configuracoes.index');
        Route::put('/configuracoes', [ConfiguracaoController::class, 'update'])->name('configuracoes.update');
    });
});

require __DIR__.'/auth.php';
