<?php

namespace App\Policies;

use App\Enums\StatusInvestigacao;
use App\Models\Investigacao;
use App\Models\User;

/**
 * Acesso às investigações de causa raiz.
 */
class InvestigacaoPolicy
{
    public function viewAny(User $usuario): bool
    {
        return $usuario->can('investigacao.gerenciar');
    }

    public function view(User $usuario, Investigacao $investigacao): bool
    {
        return $usuario->can('investigacao.gerenciar');
    }

    public function create(User $usuario): bool
    {
        return $usuario->can('investigacao.gerenciar');
    }

    public function update(User $usuario, Investigacao $investigacao): bool
    {
        return $usuario->can('investigacao.gerenciar')
            && $investigacao->status !== StatusInvestigacao::Concluida
            && $investigacao->status !== StatusInvestigacao::Cancelada;
    }

    /** Concluir exige ao menos uma causa marcada como raiz. */
    public function concluir(User $usuario, Investigacao $investigacao): bool
    {
        return $this->update($usuario, $investigacao) && $investigacao->podeConcluir();
    }
}
