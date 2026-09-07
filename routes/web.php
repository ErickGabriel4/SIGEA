<?php

use App\Http\Controllers\Admin\CategoriaEventoController;
use App\Http\Controllers\Admin\ConfiguracaoController;
use App\Http\Controllers\Admin\SetorController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\ComentarioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventoAdversoController;
use App\Http\Controllers\EventoAnexoController;
use Illuminate\Support\Facades\Route;

/*
 * Mapa de rotas do SIGEA (Seção 7 do plano de desenvolvimento).
 * Negação por padrão: toda rota autenticada exige permissão explícita
 * (spatie/laravel-permission) ou Policy.
 */

// Raiz: convidado → login; gestor → painel; notificador → suas notificações
Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->can('dashboard.ver')
        ? redirect()->route('painel.index')
        : redirect()->route('eventos.meus');
});

Route::middleware('auth')->group(function () {
    // ── Painel (T02) — permission:dashboard.ver ────────────────────────
    Route::middleware('permission:dashboard.ver')->prefix('painel')->name('painel.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
    });

    // ── Eventos adversos (T04–T08) ─────────────────────────────────────
    Route::prefix('eventos')->name('eventos.')->group(function () {
        Route::get('/', [EventoAdversoController::class, 'index'])->name('index');
        Route::get('/novo', [EventoAdversoController::class, 'create'])
            ->middleware('permission:evento.criar')->name('create');
        Route::post('/', [EventoAdversoController::class, 'store'])
            ->middleware('permission:evento.criar')->name('store');
        Route::get('/meus', [EventoAdversoController::class, 'meus'])
            ->middleware('permission:evento.ver_proprios')->name('meus');

        Route::get('/{evento}', [EventoAdversoController::class, 'show'])->name('show');
        Route::get('/{evento}/editar', [EventoAdversoController::class, 'edit'])->name('edit');
        Route::put('/{evento}', [EventoAdversoController::class, 'update'])->name('update');
        Route::delete('/{evento}', [EventoAdversoController::class, 'destroy'])->name('destroy');
        Route::post('/{evento}/enviar', [EventoAdversoController::class, 'enviar'])->name('enviar');

        // ── Comentários e anexos (objetivo específico e) ────────────────
        Route::post('/{evento}/comentarios', [ComentarioController::class, 'store'])->name('comentario.store');

        Route::post('/{evento}/anexos', [EventoAnexoController::class, 'store'])->name('anexo.store');
        Route::get('/{evento}/anexos/{anexo}', [EventoAnexoController::class, 'download'])->name('anexo.download');
        Route::delete('/{evento}/anexos/{anexo}', [EventoAnexoController::class, 'destroy'])->name('anexo.destroy');
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
