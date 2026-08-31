<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Atualiza users.ultimo_acesso_em no máximo a cada 5 minutos por usuário.
 */
class RegistrarUltimoAcesso
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::user();

        if ($usuario !== null) {
            $desatualizado = $usuario->ultimo_acesso_em === null
                || $usuario->ultimo_acesso_em->lt(now()->subMinutes(5));

            if ($desatualizado) {
                // updateQuietly: não dispara eventos nem trilha de auditoria
                $usuario->forceFill(['ultimo_acesso_em' => now()])->saveQuietly();
            }
        }

        return $next($request);
    }
}
