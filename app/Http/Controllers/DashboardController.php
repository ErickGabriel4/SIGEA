<?php

namespace App\Http\Controllers;

use App\Models\Setor;
use Illuminate\View\View;

/**
 * Painel do SIGEA (T02) — ponto de entrada dos usuários autenticados.
 * Os indicadores e gráficos são incorporados nas próximas iterações.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('painel.index', [
            'totalSetores' => Setor::ativos()->count(),
            'totalLeitos' => Setor::ativos()->sum('leitos'),
        ]);
    }
}
