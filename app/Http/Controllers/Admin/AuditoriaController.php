<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * Trilha de auditoria com diff de alterações (T21 — UC21, RF12).
 * Atende ao objetivo específico (e) do TCC.
 */
class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $registros = Activity::query()
            ->with('causer')
            ->when($request->filled('usuario_id'), fn ($q) => $q->where('causer_id', $request->integer('usuario_id')))
            ->when($request->filled('log'), fn ($q) => $q->where('log_name', $request->string('log')))
            ->when($request->filled('inicio'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('inicio')))
            ->when($request->filled('fim'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('fim')))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $logs = Activity::query()->select('log_name')->distinct()->orderBy('log_name')->pluck('log_name');

        return view('admin.auditoria.index', [
            'registros' => $registros,
            'logs' => $logs,
            'usuarios' => User::orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function show(Activity $log)
    {
        $log->load('causer');

        return view('admin.auditoria.show', ['registro' => $log]);
    }
}
