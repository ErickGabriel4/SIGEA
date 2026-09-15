<?php

use App\Http\Controllers\AcaoCorretivaController;
use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CategoriaEventoController;
use App\Http\Controllers\Admin\ConfiguracaoController;
use App\Http\Controllers\Admin\SetorController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\ComentarioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventoAdversoController;
use App\Http\Controllers\EventoAnexoController;
use App\Http\Controllers\InvestigacaoCausaController;
use App\Http\Controllers\InvestigacaoController;
use App\Http\Controllers\NotificacaoController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\TriagemController;
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
    // ── Painel (T03) — permission:dashboard.ver ─────────────────────────
    Route::middleware('permission:dashboard.ver')->prefix('painel')->name('painel.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');

        Route::middleware('throttle:60,1')->prefix('dados')->group(function () {
            Route::get('/serie-temporal', [DashboardController::class, 'serieTemporal'])->name('serie');
            Route::get('/por-categoria', [DashboardController::class, 'porCategoria'])->name('categoria');
            Route::get('/por-setor', [DashboardController::class, 'porSetor'])->name('setor');
            Route::get('/por-grau-dano', [DashboardController::class, 'porGrauDano'])->name('dano');
            Route::get('/matriz-risco', [DashboardController::class, 'matrizRisco'])->name('matriz');
            Route::get('/indicadores', [DashboardController::class, 'indicadores'])->name('indicadores');
        });
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
        Route::get('/exportar', [EventoAdversoController::class, 'exportar'])
            ->middleware('permission:evento.exportar')->name('exportar');

        Route::get('/{evento}', [EventoAdversoController::class, 'show'])->name('show');
        Route::get('/{evento}/editar', [EventoAdversoController::class, 'edit'])->name('edit');
        Route::put('/{evento}', [EventoAdversoController::class, 'update'])->name('update');
        Route::delete('/{evento}', [EventoAdversoController::class, 'destroy'])->name('destroy');
        Route::post('/{evento}/enviar', [EventoAdversoController::class, 'enviar'])->name('enviar');
        Route::post('/{evento}/cancelar', [EventoAdversoController::class, 'cancelar'])->name('cancelar');
        Route::get('/{evento}/pdf', [EventoAdversoController::class, 'pdf'])->name('pdf');

        // ── Comentários e anexos (objetivo específico e) ────────────────
        Route::post('/{evento}/comentarios', [ComentarioController::class, 'store'])->name('comentario.store');

        Route::post('/{evento}/anexos', [EventoAnexoController::class, 'store'])->name('anexo.store');
        Route::get('/{evento}/anexos/{anexo}', [EventoAnexoController::class, 'download'])->name('anexo.download');
        Route::delete('/{evento}/anexos/{anexo}', [EventoAnexoController::class, 'destroy'])->name('anexo.destroy');
    });

    // ── Triagem (T09/T10) — permission:evento.triar ─────────────────────
    Route::middleware('permission:evento.triar')->prefix('triagem')->name('triagem.')->group(function () {
        Route::get('/', [TriagemController::class, 'index'])->name('index');
        Route::post('/{evento}/assumir', [TriagemController::class, 'assumir'])->name('assumir');
        Route::get('/{evento}', [TriagemController::class, 'edit'])->name('edit');
        Route::put('/{evento}', [TriagemController::class, 'update'])->name('update');
        Route::post('/{evento}/encaminhar', [TriagemController::class, 'encaminhar'])->name('encaminhar');
        Route::post('/{evento}/concluir', [TriagemController::class, 'concluir'])->name('concluir');
        Route::post('/{evento}/feedback', [TriagemController::class, 'feedback'])->name('feedback');
        Route::post('/{evento}/notivisa', [TriagemController::class, 'registrarNotivisa'])
            ->middleware('permission:notivisa.gerenciar')->name('notivisa');
    });

    // ── Investigações de causa raiz (T11/T12) — permission:investigacao.gerenciar ──
    Route::middleware('permission:investigacao.gerenciar')->group(function () {
        Route::get('/investigacoes', [InvestigacaoController::class, 'index'])->name('investigacoes.index');
        Route::get('/eventos/{evento}/investigacao/nova', [InvestigacaoController::class, 'create'])->name('investigacoes.create');
        Route::post('/eventos/{evento}/investigacao', [InvestigacaoController::class, 'store'])->name('investigacoes.store');
        Route::get('/investigacoes/{investigacao}', [InvestigacaoController::class, 'show'])->name('investigacoes.show');
        Route::get('/investigacoes/{investigacao}/editar', [InvestigacaoController::class, 'edit'])->name('investigacoes.edit');
        Route::put('/investigacoes/{investigacao}', [InvestigacaoController::class, 'update'])->name('investigacoes.update');
        Route::post('/investigacoes/{investigacao}/concluir', [InvestigacaoController::class, 'concluir'])->name('investigacoes.concluir');

        Route::post('/investigacoes/{investigacao}/causas', [InvestigacaoCausaController::class, 'store'])->name('causas.store');
        Route::put('/investigacoes/{investigacao}/causas/{causa}', [InvestigacaoCausaController::class, 'update'])->name('causas.update');
        Route::delete('/investigacoes/{investigacao}/causas/{causa}', [InvestigacaoCausaController::class, 'destroy'])->name('causas.destroy');
    });

    // ── Ações corretivas (T13–T15) ───────────────────────────────────────
    Route::prefix('acoes')->name('acoes.')->group(function () {
        Route::get('/', [AcaoCorretivaController::class, 'index'])->name('index');
        Route::get('/minhas', [AcaoCorretivaController::class, 'minhas'])->name('minhas');
        Route::get('/{acao}/editar', [AcaoCorretivaController::class, 'edit'])->name('edit');
        Route::put('/{acao}', [AcaoCorretivaController::class, 'update'])->name('update');
        Route::delete('/{acao}', [AcaoCorretivaController::class, 'destroy'])->name('destroy');
        Route::post('/{acao}/concluir', [AcaoCorretivaController::class, 'concluir'])->name('concluir');
        Route::post('/{acao}/avaliar', [AcaoCorretivaController::class, 'avaliar'])->name('avaliar');
    });
    Route::get('/eventos/{evento}/acoes/nova', [AcaoCorretivaController::class, 'create'])->name('acoes.create');
    Route::post('/eventos/{evento}/acoes', [AcaoCorretivaController::class, 'store'])->name('acoes.store');

    // ── Relatórios (T16) — permission:relatorio.ver ────────────────────
    Route::middleware('permission:relatorio.ver')->prefix('relatorios')->name('relatorios.')->group(function () {
        Route::get('/', [RelatorioController::class, 'index'])->name('index');
        Route::get('/consolidado', [RelatorioController::class, 'consolidado'])->name('consolidado');
        Route::get('/por-setor', [RelatorioController::class, 'porSetor'])->name('setor');
        Route::get('/por-categoria', [RelatorioController::class, 'porCategoria'])->name('categoria');
        Route::get('/acoes-pendentes', [RelatorioController::class, 'acoesPendentes'])->name('acoes');
        Route::get('/{tipo}/pdf', [RelatorioController::class, 'pdf'])->name('pdf');
        Route::get('/{tipo}/excel', [RelatorioController::class, 'excel'])->name('excel');
    });

    // ── Notificações in-app (T22) ──────────────────────────────────────
    Route::prefix('notificacoes')->name('notificacoes.')->group(function () {
        Route::get('/', [NotificacaoController::class, 'index'])->name('index');
        Route::get('/contador', [NotificacaoController::class, 'contador'])->name('contador');
        Route::post('/{id}/marcar-lida', [NotificacaoController::class, 'marcarLida'])->name('lida');
        Route::post('/marcar-todas', [NotificacaoController::class, 'marcarTodas'])->name('todas');
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

        // ── Trilha de auditoria (T21) ───────────────────────────────────
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
        Route::get('/auditoria/{log}', [AuditoriaController::class, 'show'])->name('auditoria.show');
    });
});

require __DIR__.'/auth.php';
