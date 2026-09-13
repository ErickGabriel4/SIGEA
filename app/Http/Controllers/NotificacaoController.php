<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Notificações in-app do usuário (T22).
 */
class NotificacaoController extends Controller
{
    public function index(Request $request)
    {
        $notificacoes = $request->user()
            ->notifications()
            ->paginate(15);

        return view('notificacoes.index', ['notificacoes' => $notificacoes]);
    }

    /** JSON para o sino da navbar (AdminLTE navbar-notification). */
    public function contador(Request $request)
    {
        $naoLidas = $request->user()->unreadNotifications()->count();

        return response()->json([
            'label' => $naoLidas > 0 ? (string) $naoLidas : '',
        ]);
    }

    public function marcarLida(Request $request, string $id)
    {
        $notificacao = $request->user()->notifications()->findOrFail($id);
        $notificacao->markAsRead();

        $url = $notificacao->data['url'] ?? null;

        return $url ? redirect($url) : back();
    }

    public function marcarTodas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('sucesso', 'Todas as notificações foram marcadas como lidas.');
    }
}
