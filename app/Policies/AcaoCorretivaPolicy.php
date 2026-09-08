<?php

namespace App\Policies;

use App\Enums\StatusAcao;
use App\Models\AcaoCorretiva;
use App\Models\User;

/**
 * Acesso às ações corretivas: gestores gerenciam; responsáveis executam as suas.
 */
class AcaoCorretivaPolicy
{
    public function viewAny(User $usuario): bool
    {
        return $usuario->can('acao.gerenciar') || $usuario->can('acao.executar');
    }

    public function view(User $usuario, AcaoCorretiva $acao): bool
    {
        return $usuario->can('acao.gerenciar')
            || ($usuario->can('acao.executar') && $acao->responsavel_id === $usuario->id);
    }

    public function create(User $usuario): bool
    {
        return $usuario->can('acao.gerenciar');
    }

    public function update(User $usuario, AcaoCorretiva $acao): bool
    {
        return $usuario->can('acao.gerenciar')
            && $acao->status !== StatusAcao::Concluida
            && $acao->status !== StatusAcao::Cancelada;
    }

    /** Concluir: gestor ou o próprio responsável, com evidência obrigatória (no Request). */
    public function concluir(User $usuario, AcaoCorretiva $acao): bool
    {
        $aberta = ! $acao->status->tratada();

        return $aberta && (
            $usuario->can('acao.gerenciar')
            || ($usuario->can('acao.executar') && $acao->responsavel_id === $usuario->id)
        );
    }

    /** Avaliar eficácia: apenas gestor, apenas ação concluída. */
    public function avaliar(User $usuario, AcaoCorretiva $acao): bool
    {
        return $usuario->can('acao.gerenciar') && $acao->status === StatusAcao::Concluida;
    }

    public function delete(User $usuario, AcaoCorretiva $acao): bool
    {
        return $usuario->can('acao.gerenciar') && ! $acao->status->tratada();
    }
}
