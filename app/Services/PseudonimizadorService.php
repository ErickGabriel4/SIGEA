<?php

namespace App\Services;

/**
 * Pseudonimização do identificador do paciente (Seção 5.4 / LGPD).
 * Nunca armazena nome, CPF ou prontuário real: grava hash SHA-256 do
 * identificador concatenado à APP_KEY, truncado em 16 caracteres.
 */
class PseudonimizadorService
{
    /**
     * Converte o identificador interno digitado em código pseudonimizado
     * determinístico (permite agrupar eventos do mesmo paciente).
     */
    public function codificar(?string $identificador): ?string
    {
        $identificador = trim((string) $identificador);

        if ($identificador === '') {
            return null;
        }

        // Identificadores já pseudonimizados não são recodificados
        if (str_starts_with($identificador, 'PAC-')) {
            return $identificador;
        }

        $hash = hash('sha256', $identificador . config('app.key'));

        return 'PAC-' . substr($hash, 0, 16);
    }
}
