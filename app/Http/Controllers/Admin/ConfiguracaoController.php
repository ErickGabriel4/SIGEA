<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoConfiguracao;
use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use Illuminate\Http\Request;

/**
 * Parâmetros do sistema agrupados por seção (T20 — UC20).
 * Atende ao objetivo específico (e) do TCC.
 */
class ConfiguracaoController extends Controller
{
    public function index()
    {
        $configuracoes = Configuracao::orderBy('grupo')->orderBy('chave')->get()->groupBy('grupo');

        return view('admin.configuracoes.index', ['grupos' => $configuracoes]);
    }

    public function update(Request $request)
    {
        $valores = $request->validate([
            'configuracoes' => ['required', 'array'],
            'configuracoes.*' => ['nullable', 'string', 'max:1000'],
        ])['configuracoes'];

        foreach (Configuracao::all() as $config) {
            if (! array_key_exists($config->chave, $valores)) {
                continue;
            }

            $valor = $valores[$config->chave];

            // Booleanos vêm de checkbox: ausente = false
            if ($config->tipo === TipoConfiguracao::Booleano) {
                $valor = filter_var($valor, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }

            if ($config->tipo === TipoConfiguracao::Inteiro && ! is_numeric($valor)) {
                continue;
            }

            $config->update(['valor' => (string) $valor]);
        }

        return back()->with('sucesso', 'Configurações salvas.');
    }
}
